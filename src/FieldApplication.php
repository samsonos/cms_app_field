<?php 
namespace samson\cms\web\field;
use samsonframework\orm\ArgumentInterface;

/**
 * SamsonCMS additional fields application
 * @package samson\cms\web\field
 */
class FieldApplication extends \samsoncms\Application
{
    /** @var string Application display name */
    public $name = 'Доп. поля';

    /** @var string Application identifier */
    protected $id = 'field';

    /** @var bool Hide in menu */
    public $hide = true;

    /**
     * Default module handler
     * @param int $nav Current Structure identifier
     * @param int $page Current page
     */
    public function __HANDLER($nav = 0, $page = 1)
    {
        // Render view
        m()->view('index')
            ->html(CMSField::renderTable($nav, $page))
            ->title('Дополнительные поля');
    }

    /**
     * Creating list of structures
     * @param int $structure_id Current structure identifier
     *
     * @return array Ajax response
     */
    public function __async_list($structure_id)
    {
        /** @var array $return Ajax response array */
        $return = array('status' => 0);

        /** @var \samson\cms\web\navigation\CMSNav $cmsNav */
        $cmsNav = null;

        // If exists current structure
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($structure_id)->first($cmsNav)) {

            // Create view of list
            $html = m()->view('form/field_list')->structure($cmsNav)->items($cmsNav->getFieldList())->output();

            // Set positive Ajax status
            $return['status'] = 1;

            // Set view
            $return['html'] = $html;
        }

        // Return Ajax response
        return $return;
    }

    /**
     * Create form for adding or updating additional field
     * @param int  $structure_id Current structure identifier
     * @param null $field_id Current field identifier
     *
     * @return array Ajax response
     */
    public function __async_form($structure_id, $field_id = null)
    {
        /** @var array $return Ajax response array */
        $return = array('status' => 0, 'html' => '');

        // If exists current structure
        if (dbQuery('structure')->cond('StructureID', $structure_id)->first($cmsNav)) {
            // Set default field type
            $type = 0;

            // Add structure to view
            m()->set($cmsNav, 'cmsnav');
        }

        // If exists current field
        if (dbQuery('field')->id($field_id)->first($cmsField)) {
            // Get type of field
            $type = $cmsField->Type;

            // Add field to view
            m()->set($cmsField, 'field');
        }

        // Set Ajax status 1
        $return['status'] = 1;

        // Add select form to view
        m()->type_select(CMSField::createSelect($type));

        // Create view
        $html = m()->view('form/form')->output();

        $return['html'] = $html;

        // Return Ajax response
        return $return;
    }

    /**
     * Add new existed field relation to structure
     * @param int $structure_id Current structure identifier
     *
     * @return array Ajax response
     */
    public function __async_formexisted($structure_id)
    {
        /** @var array $return Ajax response array */
        $return = array('status' => 0, 'html' => '');

        // If exists current structure
        if ($this->query->entity('\samson\activerecord\structure')->where('StructureID', $structure_id)->first($cmsNav)) {
            $currentRelationIds = $this->query->entity('\samson\activerecord\structurefield')->where('StructureID', $structure_id)->fields('FieldID');

            if (count($currentRelationIds)) {
                $newFields = $this->query->entity('\samson\activerecord\field')->where('FieldID', $currentRelationIds, ArgumentInterface::NOT_EQUAL)->exec();

                $select = '<select name="field">';

                foreach ($newFields as $newField) {
                    $select .= '<option value="'.$newField->id.'">'.$newField->Name.'</option>';
                }

                $select .= '</select>';

                // Set Ajax status 1
                $return['status'] = 1;

                // Create view
                $html = $this->view('form/form_existed')->set($cmsNav, 'cmsnav')->set($select, 'field_select')->output();

                $return['html'] = $html;
            }
        }

        // Return Ajax response
        return $return;
    }

    /**
     * Save information about field or create new field
     * @param int  $structure_id Current structure identifier
     * @param null $field_id Current field identifier
     *
     * @return array Ajax response
     */
    public function __async_save($structure_id = null, $field_id = null, $edit = null)
    {
        /** @var \samson\cms\web\field\CMSField $currentField */
        $currentField = null;
        $inputName = $_POST['Name'];

        // Check input Name for illegal characters and spaces
        $returnFilter = filter_var($inputName, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/[\\\~^°!\"§$%\/()=?`'; ,\.:_{\[\]}\|<>@+#]/")));
        if ($returnFilter || $inputName == '') {
            return array('status' => 1, 'message' => t('Вы ввели некорректное значение', true));
        }

        if ($this->query->entity('\samson\cms\web\field\CMSField')->where('Name', $inputName)->where('FieldID', $field_id, ArgumentInterface::NOT_EQUAL)->first()) {
            // Field already exists. Cant use this name
            return array('status' => 1, 'message' => t('Поле с таким именем уже существует', true));
        }

        // If this is new field action
        if (!$this->query->entity('\samson\cms\web\field\CMSField')->where('FieldID', $field_id)->first($currentField)) {
            $currentField = new CMSField(false);
        }

        // Update current field
        $currentField->update($structure_id);

        // Show field in list and form or not
        $currentField->showInList = isset($_POST['show-in-list'])&&($_POST['show-in-list'] == true || $_POST['show-in-list'] == 'on') ? 1 : 0;
        $currentField->showInForm = isset($_POST['show-in-form'])&&($_POST['show-in-form'] == true || $_POST['show-in-form'] == 'on') ? 1 : 0;
        $currentField->customTypeName = isset($_POST['customTypeName'])&&($_POST['customTypeName'] != null) ? filter_var($_POST['customTypeName']) : null;
        $currentField->save();

        // Return positive Ajax status
        return $this->__async_renderfields($structure_id);
    }

    public function __async_saveexisted($structure_id = null)
    {
        if (!$this->query->entity('\samson\activerecord\structurefield')->where('StructureID', $structure_id)->where('FieldID', $_POST['field'])->first()) {
            $relation = new \samson\activerecord\structurefield();
            $relation->StructureID = $structure_id;
            $relation->FieldID = $_POST['field'];
            $relation->Active = 1;
            $relation->save();
        }
        return $this->__async_renderfields($structure_id);
    }

    /**
     * Delete relation between structure and field
     * @param int  $structure_id Current structure identifier
     * @param int $field_id Current field identifier
     *
     * @return array Ajax response
     */
    public function __async_deleterelation($structure_id, $field_id)
    {
        /** @var \samson\cms\CMSNavField $relation */
        if (dbQuery('\samson\cms\CMSNavField')->FieldID($field_id)->StructureID($structure_id)->first($relation)) {
            // Delete relation
            $relation->delete();
        }

        // Return positive Ajax status
        return $this->__async_renderfields($structure_id);
    }

    /**
     * Render additional field list of current structure
     * @param int  $structure_id Current structure identifier
     *
     * @return array Ajax response
     */
    public function __async_renderfields($structure_id)
    {
        /** @var \samson\cms\web\navigation\CMSNav $cmsNav */
        $cmsNav = dbQuery('\samson\cms\web\navigation\CMSNav')->id($structure_id)->first();

        // Return Ajax response
        return array('status' => 1, 'fields' => $cmsNav->getFieldList());
    }

    public function __async_delete($field_id) {
        // If exists current field then delete it
        if (dbQuery('field')->id($field_id)->first($field)) {
            $field->delete();

            /** @var array $matRelations array of materialfield relations */
            $matRelations = null;

            // Delete all relations between current field and materials
            if (dbQuery('materialfield')->FieldID($field_id)->exec($matRelations)) {
                foreach ($matRelations as $matRelation) {
                    $matRelation->delete();
                }
            }

            /** @var array $strRelations array of structurefield relations */
            $strRelations = null;

            // Delete all relations between current field and structures
            if (dbQuery('structurefield')->FieldID($field_id)->exec($strRelations)) {
                foreach ($strRelations as $strRelation) {
                    $strRelation->delete();
                }
            }
        }

        // Return positive Ajax status
        return array('status' => 1);
    }

    public function __async_updatetable($nav_id = null, $page = 1)
    {
        /** @var \samson\pager\Pager $pager */
        $pager = null;
        // Return Ajax response
        return array('status' => 1, 'table' => CMSField::renderTable($nav_id, $page, $pager), 'pager' => $pager->toHTML());
    }
}
