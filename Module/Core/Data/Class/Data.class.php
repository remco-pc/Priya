<?php
/**
 * @author 		Remco van der Velde
 * @since 		2016-10-19
 * @version		1.0
 * @changeLog
 * 	-	all
 */
namespace Priya\Module\Core;

use Priya\Module\Core;
use Priya\Application;
use Priya\Module\File;
use stdClass;

class Data extends Core {
    const DIR = __DIR__;

    private $url;
    private $data;

    public function __construct($handler=null, $route=null, $data=null){
        $this->data($this->object_merge($this->data(), $data));
        parent::__construct($handler, $route);
    }

    public function data($attribute=null, $value=null){
        if($attribute !== null){
            if($value !== null){
                if($attribute=='delete'){
                    return $this->deleteData($value);
                } else {
                    $this->object_set($attribute, $value, $this->data());
                }
            } else {
                if(is_string($attribute)){
                    return $this->object_get($attribute, $this->data());
                } else {
                    $this->setData($attribute);
                    return $this->getData();
                }
            }
        }
        return $this->getData();
    }

    private function setData($attribute='', $value=null){
        if(is_array($attribute) || is_object($attribute)){
            if(is_object($this->data)){
                foreach($attribute as $key => $value){
                    $this->data->{$key} = $value;
                }
            }
            elseif(is_array($this->data)){
                foreach($attribute as $key => $value){
                    $this->data[$key] = $value;
                }
            } else {
                $this->data = $attribute;
            }
        } else {
            if(is_object($this->data)){
                $this->data->{$attribute} = $value;
            }
            elseif(is_array($this->data)) {
                $this->data[$attribute] = $value;
            } else {
                var_dump('setData create object and set object');
            }
        }
    }

    private function getData($attribute=null){
        if($attribute === null){
            if(is_null($this->data)){
                $this->data = new stdClass();
            }
            return $this->data;
        }
        if(isset($this->data[$attribute])){
            return $this->data[$attribute];
        } else {
            return false;
        }
    }

    private function deleteData($attribute=null){
        return $this->object_delete($attribute, $this->data());
    }

    public function url($url=null){
        if($url !== null){
            $this->setUrl($url);
        }
        return $this->getUrl();
    }

    private function setUrl($url=''){
        $this->url = $url;
    }

    private function getUrl(){
        return $this->url;
    }

    public function read($url=''){
        $namespace = '';
        if(empty($url)){
            $url = get_called_class();
        }
        if(file_exists($this->url($url))){
            $file = new File();
            $read = $file->read($url);
            $read = $this->object($read);
            $data = $this->data();
            if(empty($data)){
                $data = new stdClass();
            }
            if(!empty($read)){
                if(is_array($read) || is_object($read)){
                    foreach($read as $attribute => $value){
                        $this->object_set($attribute, $value, $data);
                    }
                } else {
                    var_dump($read);
                    die;
                }
            }
            return $this->data($data);
        } else {
            $module = $url;
        }
        $tmp = explode('\\', trim(str_replace(Application::DS, '\\',$url),'\\'));
        $class = array_pop($tmp);
        $namespace = implode('\\', $tmp);
        $directory = explode(Application::DS, Application::DIR);
        array_pop($directory);
        array_pop($directory);
        $priya = array_pop($directory);
        $directory = implode(Application::DS, $directory) . Application::DS;
        if(empty($namespace)){
            $namespace = $priya . '\\' . Application::MODULE;
        }
        $directory .= str_replace('\\', Application::DS, $namespace) . Application::DS;
        $data = new \Priya\Module\Autoload\Data();
        $environment = $this->data('environment');
        if(!empty($environment)){
//             $data->environment($environment);
        }
        $class = get_called_class();
        if($class::DIR){
            $dir = dirname($class::DIR) . Application::DS;// . 'Data' . Application::DS;
            $data->addPrefix('none', $dir);
        }
        $data->addPrefix($namespace, $directory);
        $url = $data->data_load($url);
        if($url !== false){
            $this->url($url);
        }
        $file = new File();
        $read = $file->read($url);
        $read = $this->object($read);
        $data = $this->data();
        if(empty($data)){
            $data = new stdClass();
        }
        if(!empty($module)){
            $this->object_set('module', $module, $data);
            $class = str_replace('\\', '-', strtolower($module));
            $this->object_set('class', $class, $data);
            $namespace = explode('\\', $module);
            array_pop($namespace);
            $namespace = implode(Application::DS, $namespace) . Application::DS;
            $this->object_set('namespace', $namespace, $data);
        }
        if(!empty($read)){
            foreach($read as $attribute => $value){
                $this->object_set($attribute, $value, $data);
            }
        } else {
            return false;
        }
        return $this->data($data);
    }

    public function write($url=''){
        if(!empty($url)){
            $this->url($url);
        }
        $url = $this->url();
        $file = new File();
        $write = $file->write($url, $this->object($this->data(), 'json'));
        return $write;
    }

    public function search($list, $find, $attribute=null, $case=false, $not=false){
        $useData = true;
        $output = 'array';
        if(is_string($list)){
            $data = $this->data($list);
        } else {
            $useData = false;
            $data = $list;
        }
        if(!is_array($data)){
            $output = 'object';
        }
        $result = array();
        if(!is_array($attribute) && !is_null($attribute)){
            $attribute = explode(',', $attribute);
        }
        if(is_array($data) || is_object($data)){
            foreach($data as $key => $node){
                $search = '';
                if(is_array($attribute)){
                    foreach($attribute as $value){
                        $selector = trim($value);
                        $select = $this->object_get($selector, $node);
                        $search .= $select . ' ';
                    }
                }
                if(empty($case)){
                    $search = strtolower($search);
                    $find = strtolower($find);
                }
                $search = trim($search);
                $find = trim($find);
                $levenshtein = levenshtein($search, $find, 5, 2, 5);
                if(!empty($not)){
                    if(strstr($search, $find) === false){
                        $result[$levenshtein][$key] = $node;
                    }
                } else {
                    if(strstr($search, $find) !== false){
                        $result[$levenshtein][$key] = $node;
                    }
                }
            }
        }
        $data = array();
        $sort = SORT_NATURAL;
        if(!empty($not) == 'desc'){
            krsort($result, $sort);
        } else {
            ksort($result, $sort);
        }
        foreach($result as $levenshtein => $subList){
            foreach($subList as $key => $node){
                $data[$key] = $node;
            }
        }
        if(empty($useData)){
            return $this->object($data, $output);
        } else {
            $this->data('delete', $list);
            return $this->data($list, $this->object($data, $output));
        }
    }

    public function sort($list, $attribute, $order='ASC', $sort=null, $case=false){
        $useData = true;
        $output = 'array';
        if(is_string($list)){
            $data = $this->data($list);
        } else {
            $useData = false;
            $data = $list;
        }
        if(!is_array($data)){
            $output = 'object';
        }
        $result = array();
        if(!is_array($attribute)){
            $attribute = explode(',', $attribute);
        }
        if(is_array($data) || is_object($data)){
            foreach($data as $key => $node){
                $sorter = '';
                foreach($attribute as $value){
                    $selector = trim($value);
                    $select = $this->object_get($selector, $node);
                    $sorter .= $select;
                }
                if(empty($case)){
                    $sorter = strtolower($sorter);
                }
                $result[$sorter][$key] = $node;
            }
        }
        if($sort === null){
            $sort = SORT_NATURAL;
        }
        if(strtolower($order) == 'desc'){
            krsort($result, $sort);
        } else {
            ksort($result, $sort);
        }
        $data = array();
        foreach($result as $sorter => $subList){
            foreach($subList as $key => $node){
                $data[$key] = $node;
            }
        }
        if(empty($useData)){
            return $this->object($data, $output);
        } else {
            $this->data('delete', $list);
            return $this->data($list, $this->object($data, $output));
        }
    }

    public function filter($list='', $attribute=array(), $values=array(), $action='keep'){
        $useData = true;
        $output = 'array';
        if(is_string($list)){
            $data = $this->data($list);
        } else {
            $useData = false;
            $data = $list;
        }
        if(!is_array($data)){
            $output = 'object';
        }
        $result = array();
        $remove = array();
        if(!is_array($attribute)){
            $attribute = explode(',', $attribute);
        }
        if(is_object($values)){
            $values = $this->object($values, 'array');
            var_dump($values);
            die;
        }
        elseif(!is_array($values)){
            $values = explode(',', $values);
        }
        if(is_array($data) || is_object($data)){
            foreach($data as $key => $node){
                foreach($attribute as $value){
                    $selector = trim($value);
                    $select = $this->object_get($selector, $node);
                    if($action == 'remove'){
                        if(!empty($select)){
                            $remove[$key] = true;
                        }
                        $result[$key] = $node;
                    }
                    elseif($action == 'keep' && !empty($select)){
                        if(empty($values)){
                            $result[$key] = $node;
                        } else {
                            if(is_array($select)){
                                foreach($values as $val){
                                    if(in_array($val, $select)){
                                        $result[$key] = $node;
                                        break;
                                    }
                                }
                            } else {
                                foreach($values as $val){
                                    if($val == $select){
                                        $result[$key] = $node;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if(!empty($remove)){
            foreach ($remove as $key => $true){
                unset($result[$key]);
            }
        }
        if(empty($useData)){
            return $this->object($result, $output);
        } else {
            $this->data('delete', $list);
            return $this->data($list, $this->object($result, $output));
        }
    }

    public function count($nodeList='', $attribute='', $value='', $value_attribute='', $count='count'){
        if(empty($nodeList)){
            return;
        }
        if(empty($attribute)){
            return;
        }
        foreach($nodeList as $node){
            if(!isset($node->{$attribute})){
                continue;
            }
            if(is_array($node->{$attribute})){
                foreach ($node->{$attribute} as $attribute_value){
                    if(is_object($value)){
                        foreach($value as $object){
                            if($object->{$value_attribute} == $attribute_value){
                                if(!isset($object->{$count})){
                                    $object->{$count} =1;
                                } else {
                                    $object->{$count}++;
                                }
                            }
                        }
                    }
                }
            } else {
                if(is_object($value)){
                    foreach($value as $object){
                        if($object->{$value_attribute} == $node->{$attribute}){
                            if(!isset($object->{$count})){
                                $object->{$count} =1;
                            } else {
                                $object->{$count}++;
                            }
                        }
                    }
                }

            }
        }
    }

    public function parent($list='', $attribute='', $value='', $children='nodeList', $parent=null){
        if(is_object($list)){
            foreach($list as $key => $node){
                if(isset($node->{$attribute}) && $node->{$attribute} == $value){
                    if(empty($parent)){
                        return null;
                    } else {
                        return $parent;
                    }
                }
                if(!empty($node->{$children})){
                    $node_parent = $this->parent($node->{$children}, $attribute, $value, $children, $node);
                    if(!empty($node_parent)){
                        return $node_parent;
                    }
                }
            }
        }
        return false;
    }

    public function recursive($list='', $attribute='', $value='', $children='nodeList'){
        if(is_object($list)){
            foreach($list as $key => $node){
                if(isset($node->{$attribute}) && $node->{$attribute} == $value){
                    return $node;
                }
                if(!empty($node->{$children})){
                    $recursive = $this->recursive($node->{$children}, $attribute, $value, $children);
                    if(!empty($recursive)){
                        return $recursive;
                    }
                }
            }
        }
        return false;
    }

    public function recursive_sort($list='', $attribute='', $order='ASC', $children='nodeList', $sort=null, $case=false){
        $nodeList = $this->sort($list, $attribute, $order, $sort=null, $case=false);
        foreach($nodeList as $key => $node){
            if(is_object($node) && !empty($node->{$children})){
                $node->{$children} = $this->recursive_sort($node->{$children}, $attribute, $order, $children, $sort=null, $case=false);
            }
        }
        return $nodeList;
    }

    public function recursive_delete($list='', $attribute='', $value='', $children='nodeList'){
        if(is_object($list)){
            foreach($list as $key => $node){
                if($node->{$attribute} == $value){
                    unset($list->{$key});
                }
                if(isset($node->{$children})){
                    $this->recursive_delete($node->{$children}, $attribute, $value, $children);
                }
            }
        }
    }

    public function flatten($list='', $children='nodeList'){
        if(is_object($list)){
            foreach($list as $key => $node){
                if(isset($node->{$children})){
                    $nodeList = $this->flatten($node->{$children}, $children);
                    if(is_object($nodeList)){
                        foreach($nodeList as $child_key => $child){
                            $list->$child_key = $child;
                        }
                    }
                }
            }
        }
        return $list;
    }

    public function node($attribute='', $node='', $merge=false){
        $url = $this->url();
        if(empty($url)){
            return false;
        }
        if(empty($attribute)){
            return false;
        }
        if(empty($node)){
            return false;
        }
        $nodeList = $this->data($attribute);
        if(empty($node->jid)){
            $node->jid = $this->jid($attribute);
            if(empty($nodeList)){
                $nodeList = new stdClass();
            }
            $nodeList->{$node->jid} = $node;
            $this->data($attribute, $nodeList);
            $this->write($url);
            return $node->jid;
        } else {
            $update = false;
            if(is_array($nodeList) || is_object($nodeList)){
                foreach($nodeList as $jid => $item){
                    if($jid == $node->jid){
                        $update = true;
                        if(empty($merge)){
                            $nodeList->{$jid} = $node;
                        } else {
                            $nodeList->{$jid} = $this->object_merge($item, $node);
                        }
                        break;
                    }
                }
            }
            if(empty($update)){
                return false;
            }
        }
        $this->data($attribute, $nodeList);
        $this->write($url);
        return $node->jid;
    }

    public function jid($list=''){
        if(is_array($list) || is_object($list)){
            $data = $list;
        } else {
            $data = $this->data($list);
        }
        $number = 1;
        if(empty($data)){
            return '1';
        } else {
            $tmpList = array_keys($this->object($data, 'array'));
            rsort($tmpList);
            foreach($tmpList as $nr => $jid){
                if(is_numeric($jid) && intval($jid) >= $number){
                    $number = intval($jid)+1;
                    break;
                }
            }
            return strval($number);
        }
    }

    public function copy($copy=null){
        return unserialize(serialize($copy));
    }
}