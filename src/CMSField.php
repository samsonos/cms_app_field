<?php
/**
 * Created by Pavlo Onysko <onysko@samsonos.com>
 * on 28.07.14 at 18:39
 */
 namespace samson\cms\web\field;

 use samsoncms\api\Field;

 /**
 *
 * @author Pavlo Onysko <onysko@samsonos.com>
 * @copyright 2014 SamsonOS
 * @version 
 */
class CMSField extends Field
{
    public static function renderTable($nav = 0, $page = 0, & $pager = null)
    {
        // Set new pager
        $pager = new \samson\pager\Pager($page, 5, 'field/updatetable/'.$nav.'/');

        // Create SamsonCMS fields table
        $table = new Table($pager, $nav);

        // Render view
        return m()
            ->view('index')
            ->title('Дополнительные поля')
            ->set('table', $table->render(null, $nav))
            ->set($pager)
            ->output();
    }

    /**
     * Generate HTML select element to define additional field type
     * @param int $type Current field selected type
     * @return string HTML select element code
     */
    public static function createSelect($type = 0)
    {
        // Create html view
        $html = '';

        // Define all types of data
        $typeData = array(
            'Текст' => 0,
            'Ресурс' => 1,
            'Дата' => 3,
            'Дата и время' => 10,
            'Select' => 4,
            'Таблицы' => 5,
            'Материал' => 6,
            'Число' => 7,
            'WYSIWYG' => 8,
            'Внешняя картинка' => 13,
            'Галерея' => 9
        );

        // Iterate current types
        foreach ($typeData as $key => $value) {
            // Check selected status
            $selected = ($type == $value) ? 'selected' : '';

            // Create options of select
            $html .= '<option value="' . $value . '" ' . $selected . '>' . $key . '</option>';
        }

        // Return view
        return '<select name="Type" id="Type">' . $html . '</select>';
    }

    /**
     * Get all class names of passed class
     * @param $className
     * @return array
     */
    public static function getParentClasses($className) {
        $result = array();
        $class = new \ReflectionClass($className);
        if( false === $class ) {
            return $result;
        }
        do {
            $name = $class->getName();
            $result[] = $name;
            $class = $class->getParentClass();
        } while( false !== $class );
        return $result;
    }

    public static function getChildClasses($className)
    {
        $children  = array();
        $i = 0;
        trace(get_declared_classes(), 1);
        foreach(get_declared_classes() as $class) {
            $i++;
            if ($class == 'samsonphp\cms\client\Picture') {

                trace(self::getParentClasses($class), 1);
                trace($className, 1);
                trace($class, 1);die;
            }
//            if ($i<300) {
//                continue;
//            }
            if(in_array($className, self::getParentClasses($class))) $children[] = $class;
        }
        return $children;
    }

    /**
     * Updating field and creating relation with structure
     * @param int $structureID
     */
    public function update($structureID = 0)
    {
        // Fill the fields from $_POST array
        foreach ($_POST as $key => $val) {
            $this[$key]=$val;
        }

        $this->save();
        /** @var \samson\cms\web\navigation\CMSNav $cmsnav */

        // If isset current structure
        if (dbQuery('\samson\cms\web\navigation\CMSNav')->id($structureID)->first($cmsnav)) {

            // If structure has not relation with current field
            if (!dbQuery('structurefield')->StructureID($cmsnav->id)->FieldID($this->id)->first()) {

                // Create new relation between structure and field
                $strField = new \samson\activerecord\structurefield(false);
                $strField->FieldID = $this->id;
                $strField->StructureID = $cmsnav->id;
                $strField->Active = 1;

                // Save relation
                $strField->save();
            }
        }
    }
}
