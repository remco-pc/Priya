<?php
/**
 * @author Remco van der Velde
 * @since 2014-06-12
 * @version 1.0
 *
 * @package Smarty
 * @subpackage PluginsBlock
 * @category block_json
 *
 * @description
 * Smarty plugin
 *
 */

function smarty_block_content($params, $content, $template, &$repeat)
{
    if (is_null($content)) {
        return;
    }
    $assign = null;
    $trim = null;

    $search = array(" ", "\t", "\n", "\r", "\r\n");
    $replace = array('','','','','');

    foreach ($params as $_key => $_val) {
        switch ($_key) {
            case 'assign':
            case 'name':
                $assign = (string) $_val;
            break;
            case 'trim':
            	$trim = (string) $_val;
            break;
            case 'search':
            	$search = (array) $_val;
            break;
            case 'replace':
            	$replace = (array) $_val;
            break;
            default:
                trigger_error("content_block: unknown attribute '$_key'");
        }
    }
    if($trim=='html'){
    	$content = trim($content, "\r\n\s\t");
    	$data = explode('<', $content);
    	foreach ($data as $nr => $row){
    		$dataRow = explode('>', $row);
    		if(count($dataRow)>=2){
    			foreach ($dataRow as $dataRowNr => $dataR){
    				$tmp = str_replace($search, $replace, $dataR);
    				if(empty($tmp)){
    					$dataRow[$dataRowNr] = '';
    				}
    			}
    			$data[$nr] = implode('>', $dataRow);
    		}
    	}
    	$content = implode('<', $data);
	}
	$priya = '<!-- <priya-' . str_replace('_', '-', $assign);
	$class = array();

	$search_class = trim('-' . str_replace(array(" ", "\t", "\n", "\r", "\r\n"),'',implode('-', $search)), '-');
	$replace_class = trim('-' . str_replace(array(" ", "\t", "\n", "\r", "\r\n"),'',implode('-', $replace)), '-');



	if(empty($trim)){
		$class[] = 'html-search' . $search_class;
		$class[] = 'html-replace' . $replace_class;
	} else {
		$class[] = $trim . '-trim';
		$class[] = $trim . '-search' . $search_class;
		$class[] = $trim . '-replace' . $replace_class;
	}
	$priya .= ' class="' . implode(' ', $class) . '">  //-->' . "\r\n";
	$priya .= $content;
	$priya .= '<!-- </priya-' . str_replace('_', '-', $assign) . '> //-->' . "\r\n";

    if($assign){
    	$template->assign($assign, $priya);
    } else {
    	return $priya;
    }
}