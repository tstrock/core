<?php

/**
 * Modules.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO;


class Modules
{
    /**
     * Retrieves the list of all modules currently in the database.
     *
     * @return array $module_info an ordered array of hashes, each hash being the module info
     */
    public static function get() {
        $db = Core::$db;
        $table_prefix = Core::getDbTablePrefix();

        $db->query("
            SELECT *
            FROM {$table_prefix}modules
            ORDER BY module_name
        ");
        $db->execute();


        $modules_info = array();
        foreach ($db->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $modules_info[] = $row;
        }

//        extract(ft_process_hook_calls("start", compact("modules_info"), array("modules_info")), EXTR_OVERWRITE);

        return $modules_info;
    }


    /**
     * Updates the list of modules in the database by examining the contents of the /modules folder.
     */
    public static function updateModuleList()
    {
        $table_prefix = Core::getDbTablePrefix();
        $root_dir = Core::getRootDir();

        // $LANG;

        $modules = self::getUploadedModules();

        foreach ($modules as $module) {

        }

//
//                $author               = $info["author"];
//                $author_email         = $info["author_email"];
//                $author_link          = $info["author_link"];
//                $module_version       = $info["version"];
//                $module_date          = $info["date"];
//                $is_premium           = $info["is_premium"];
//                $origin_language      = $info["origin_language"];
//                $nav                  = $info["nav"];
//
//                $module_name          = $lang_info["module_name"];
//                $module_description   = $lang_info["module_description"];

//                // convert the date into a MySQL datetime
//                list($year, $month, $day) = explode("-", $module_date);
//                $timestamp = mktime(null, null, null, $month, $day, $year);
//                $module_datetime = ft_get_current_datetime($timestamp);
//
//                mysql_query("
//    INSERT INTO {$table_prefix}modules (is_installed, is_enabled, is_premium, origin_language, module_name,
//      module_folder, version, author, author_email, author_link, description, module_date)
//    VALUES ('no','no', '$is_premium', '$origin_language', '$module_name', '$folder', '$module_version',
//      '$author', '$author_email', '$author_link', '$module_description', '$module_datetime')
//      ") or die(mysql_error());
//                $module_id = mysql_insert_id();
//
//                // now add any navigation links for this module
//                if ($module_id)
//                {
//                    $order = 1;
//                    while (list($lang_file_key, $info) = each($nav))
//                    {
//                        $url        = $info[0];
//                        $is_submenu = ($info[1]) ? "yes" : "no";
//                        if (empty($lang_file_key) || empty($url))
//                            continue;
//
//                        $display_text = isset($lang_info[$lang_file_key]) ? $lang_info[$lang_file_key] : $LANG[$lang_file_key];
//
//                        mysql_query("
//        INSERT INTO {$table_prefix}module_menu_items (module_id, display_text, url, is_submenu, list_order)
//        VALUES ($module_id, '$display_text', '$url', '$is_submenu', $order)
//        ") or die(mysql_error());
//
//                        $order++;
//                    }
//                }
//            }
//        }
//        closedir($dh);

        return array(true, $LANG["notify_module_list_updated"]);
    }


    /**
     * Examines the content of the installation's modules folder and extracts information about all
     * valid uploaded modules.
     * @return array
     */
    private static function getUploadedModules()
    {
        $root_dir = Core::getRootDir();

        $modules_folder = "$root_dir/modules";
        $dh = opendir($modules_folder);

        // if we couldn't open the modules folder, it doesn't exist or something went wrong
        if (!$dh) {
            return array(false, "");
        }

        // get the list of currently installed modules
        $current_modules = self::get();
        $current_module_folders = array();
        foreach ($current_modules as $module_info) {
            $current_module_folders[] = $module_info["module_folder"];
        }


        $modules = array();
        while (($folder = readdir($dh)) !== false) {
            // if this module is already in the database, ignore it
            if (in_array($folder, $current_module_folders)) {
                continue;
            }

            if (is_dir("$modules_folder/$folder") && $folder != "." && $folder != "..") {
                $info = ft_get_module_info_file_contents($folder);

                if (empty($info)) {
                    continue;
                }

                // check the required info file fields
                $required_fields = array("author", "version", "date", "origin_language");
                $all_found = true;
                foreach ($required_fields as $field) {
                    if (empty($info[$field])) {
                        $all_found = false;
                    }
                }
                if (!$all_found) {
                    continue;
                }

                // now check the language file contains the two required fields: module_name and module_description
                $lang_file = "$modules_folder/$folder/lang/{$info["origin_language"]}.php";
                $lang_info = _ft_get_module_lang_file_contents($lang_file);

                // check the required language file fields
                if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
                    (!isset($lang_info["module_description"]) || empty($lang_info["module_description"]))) {
                    continue;
                }

                $modules[$folder] = array(
                    "module_info" => $info,
                    "module_name" => $lang_info["module_name"],
                    "module_description" => $lang_info["module_description"]
                );
            }
        }

        return $modules;
    }
}
