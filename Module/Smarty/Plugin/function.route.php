<?php
/**
 * Smarty plugin
*
* @package Smarty
* @subpackage PluginsFunction
* @version 1.0
* @author Remco van der Velde
* @param array/object/value                    $params   parameters
* @param Smarty_Internal_Template $template template object
* @return html
*/

function smarty_function_route($params, $template)
{
    $name = '';
    $attribute = array();
    if(isset($params['name'])){
        $name = $params['name'];
    }
    if(isset($params['attribute'])){
        if(is_array($params['attribute'])){
            $attribute = $params['attribute'];
        } else {
            $attribute = (array) $params['attribute'];
        }
    }
    $vars = $template->getTemplateVars();

    if(isset($vars['route'])){
        $found = false;
        foreach($vars['route'] as $routeName => $route){
            if(!is_array($route)){
                continue;
            }
            if(!isset($route['path'])){
                continue;
            }
            if(strtolower($name) == strtolower($routeName)){
                $found = $route;
                break;
            }
        }
        if(empty($found)){
            trigger_error('Route not found for ('. $name.')', E_USER_ERROR);
        } else {
            $route_path = explode('/', trim(strtolower($route['path']), '/'));
            foreach($route_path as $part_nr => $part){
                if(substr($part,0,1) == '{' && substr($part,-1) == '}'){
                    $route_path[$part_nr] = array_shift($attribute);
                }
                if(empty($route_path[$part_nr])){
                    unset($route_path[$part_nr]);
                }
            }
            $path = implode('/', $route_path);
            if(!empty($path)){
                $path .= '/';
            }
            if(stristr($path, 'http') === false){
                if(isset($vars['web']) && isset($vars['web']['root'])){
                    $path = $vars['web']['root'] . $path;
                }
            }
            return $path;
        }
    }

}
