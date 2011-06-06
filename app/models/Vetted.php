<?php
namespace php_active_record;

class Vetted extends ActiveRecord
{
    static $table_name = 'vetted';
    
    public static function trusted()
    {
        return Vetted::find_or_create_by_label('Trusted', array('created_at' => 'NOW()', 'updated_at' => 'NOW()', 'view_order' => 1));
    }
    
    public static function unknown()
    {
        return Vetted::find_or_create_by_label('Unknown', array('created_at' => 'NOW()', 'updated_at' => 'NOW()', 'view_order' => 2));
    }
    
    public static function untrusted()
    {
        return Vetted::find_or_create_by_label('Untrusted', array('created_at' => 'NOW()', 'updated_at' => 'NOW()', 'view_order' => 3));
    }
}

?>