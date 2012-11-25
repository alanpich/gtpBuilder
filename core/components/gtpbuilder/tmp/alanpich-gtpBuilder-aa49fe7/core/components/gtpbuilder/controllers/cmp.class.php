<?php
/**
 * Main CMP controller
 * 
 * @package grideditor
 * @copyright Alan Pich 2012
 */
class gtpBuilderCmpManagerController extends gtpBuilderManagerController {
    
    /** bool $validConfig Is there a valid config file? */
    private $validConfig = true;
    
  
    /**
     * Allow config chunk to override page title
     * @return string Page Title
     */
    public function getPageTitle() { 
        return "GitHub Transport Package Builder";
    }//
    
    
    /**
     * Add all nescesary JS to page
     */
    public function loadCustomCssJs() {

    }//
    
    /**
     * Check config file exists, either return cmp tpl or error message
     * @return string Path to smarty template
     */
    public function getTemplateFile() {
        return $this->helper->config['tpl_path'].'cmp.tpl';
    }//


    
}// end class
