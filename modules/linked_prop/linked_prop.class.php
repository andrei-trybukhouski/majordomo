<?php
class linked_prop extends module
{
    function __construct()
    {
        $this->name = "linked_prop";
        $this->title = "Связанные свойства";
        $this->module_category = "<#LANG_SECTION_SYSTEM#>";
        $this->checkInstalled();
    }

    function saveParams($data = 1)
    {
        $p = array();
        if (IsSet($this->id))
        {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode))
        {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode))
        {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab))
        {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id))
        {
            $this->id = $id;
        }
        if (isset($mode))
        {
            $this->mode = $mode;
        }
        if (isset($view_mode))
        {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode))
        {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab))
        {
            $this->tab = $tab;
        }
    }

    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin')
        {
            $this->admin($out);
        }
        else
        {
            $this->usual($out);
        }
        if (IsSet($this->owner->action))
        {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name))
        {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    function admin(&$out) {
		$select = SQLSelect("SELECT * FROM `pvalues` WHERE `LINKED_MODULES` != ''");
		$moduleList = [];
		
		foreach($select as $value) {
			$explodeModules = explode(',', $value['LINKED_MODULES']);
			
			foreach($explodeModules as $exModules) {
				if(!in_array($exModules, $moduleList)) {
					$moduleList[] = $exModules;				
				}
			}
		}
		$newModuleList = [];
		
		foreach($moduleList as $key => $modList) {
			$getModuleName = SQLSelectOne("SELECT * FROM `project_modules` WHERE NAME = '".$modList."'");
			$countLink = SQLSelectOne("SELECT COUNT(*) FROM `pvalues` WHERE `LINKED_MODULES` LIKE '%".$modList."%'");
			
			$newModuleList[$key]['HUMAN_NAME'] = $getModuleName['TITLE'];
			$newModuleList[$key]['SYSTEM_NAME'] = $modList;
			$newModuleList[$key]['TOTAL_LINK'] = $countLink['COUNT(*)'];
		}
		
		unset($moduleList);

		$out['MODULES'] = $newModuleList;
		
		if(!empty(strip_tags($this->view_mode))) {
			$select = SQLSelect("SELECT * FROM `pvalues` WHERE `LINKED_MODULES` LIKE '%".DBSafe($this->view_mode)."%'");
			$out['MODULE_LINKED'] = $select;
		}
		
		if(!empty(strip_tags($this->view_mode)) && !empty(strip_tags($this->edit_mode))) {
			$str = explode('.', $this->edit_mode);
			$obj = $str[0];
			$prop = $str[1];
			
			removeLinkedProperty($obj, $prop, $this->view_mode);
			$this->redirect('?');
		}
    }

    function usual(&$out) {
        $this->admin($out);
    }

    function install($data = '')
    {
        parent::install();
    }
}

