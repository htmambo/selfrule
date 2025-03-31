<?php

use Symfony\Component\Yaml\Yaml;

class RuleOptimizer {
    private $rules = [];
    private $ruleGroups = [];
    
    public function __construct($yamlFile) {
        $content = Yaml::parseFile($yamlFile);
        $this->rules = $content['rules'] ?? [];
    }
    
    public function optimize() {
        // 按规则类型分组
        $this->groupRules();
        
        // 去重并保留优先级高的规则
        $this->removeDuplicates();
        
        // 重新排序
        $this->sortRules();
        
        return $this->rules;
    }
    
    private function groupRules() {
        foreach ($this->rules as $rule) {
            list($type, $pattern, $proxy) = explode(',', $rule);
            $noResolve = isset(explode(',', $rule)[3]) && trim(explode(',', $rule)[3]) === 'no-resolve';
            
            $key = $type . ',' . $pattern;
            
            // 如果规则已存在且新规则不是"节点选择"，则更新
            if (!isset($this->ruleGroups[$key]) || $proxy !== '🚀 节点选择') {
                $this->ruleGroups[$key] = [
                    'type' => $type,
                    'pattern' => $pattern,
                    'proxy' => $proxy,
                    'no_resolve' => $noResolve
                ];
            }
        }
    }
    
    private function removeDuplicates() {
        $this->rules = [];
        foreach ($this->ruleGroups as $rule) {
            $ruleStr = $rule['type'] . ',' . $rule['pattern'] . ',' . $rule['proxy'];
            if ($rule['no_resolve']) {
                $ruleStr .= ',no-resolve';
            }
            $this->rules[] = $ruleStr;
        }
    }
    
    private function sortRules() {
        // 将规则分为带 no-resolve 和不带 no-resolve 的两组
        $noResolveRules = [];
        $normalRules = [];
        
        foreach ($this->rules as $rule) {
            if (strpos($rule, 'no-resolve') !== false) {
                $noResolveRules[] = $rule;
            } else {
                $normalRules[] = $rule;
            }
        }
        
        // 分别对两组规则按代理名称排序
        usort($noResolveRules, [$this, 'sortByProxy']);
        usort($normalRules, [$this, 'sortByProxy']);
        
        // 合并结果
        $this->rules = array_merge($noResolveRules, $normalRules);
    }
    
    private function sortByProxy($a, $b) {
        $proxyA = explode(',', $a)[2];
        $proxyB = explode(',', $b)[2];
        return strcmp($proxyA, $proxyB);
    }
    
    public function saveToFile($outputFile) {
        $content = Yaml::parseFile('/Volumes/Workarea/zhp/src/selfrule/full.yaml');
        $content['rules'] = $this->rules;
        file_put_contents($outputFile, Yaml::dump($content, 4, 2));
    }
}

// 使用示例
require_once 'vendor/autoload.php';
$optimizer = new RuleOptimizer('/Volumes/Workarea/zhp/src/selfrule/full.yaml');
$optimizer->optimize();
$optimizer->saveToFile('/Volumes/Workarea/zhp/src/selfrule/full_optimized.yaml');