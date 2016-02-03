<?php 
namespace samson\cms\web\field;

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
     * Save information about field or create new field
     * @param int  $structure_id Current structure identifier
     * @param null $field_id Current field identifier
     *
     * @return array Ajax response
     */
    public function __async_save($structure_id = null, $field_id = null)
    {
        // check input Name for illegal characters and spaces
        $pattern = "/[\\\~^°!\"§$%\/()=?`';,\.:_{\[\]}\|<>@+#]/";
        if (preg_match($pattern, $_POST['Name']) || strpos($_POST['Name'], 0x20) || $_POST['Name'] == '') {
            return array('status' => 1, 'message' => t('Вы ввели некорректное значение', true));
        }
        // If not exists current field
        else if (!dbQuery('\samson\cms\web\field\CMSField')->where('Name', $_POST['Name'])->first($field)) {
            // Create new field
            $field = new CMSField(false);
        } else {
            return array('status' => 1, 'message' => t('Поле с таким именем уже существует', true));
        }

        // Update field data
        $field->update($structure_id);

        // Return positive Ajax status
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
