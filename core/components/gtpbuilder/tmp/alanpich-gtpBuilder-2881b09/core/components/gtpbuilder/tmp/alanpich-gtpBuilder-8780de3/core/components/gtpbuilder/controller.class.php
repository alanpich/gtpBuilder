<?php
/**
 * Abstract & Base CMP controllers
 * 
 * @package gtpbuilder
 * @copyright Alan Pich 2012
 */

/**
 * @abstract Manager Controller Global Setup
 */
abstract class gtpBuilderManagerController extends modExtraManagerController {
    /** @var gtpBuilder $helper */
    public $helper;
    
    public function initialize() {
        // Require the grideditor service
        $path = $this->modx->getOption('core_path').'components/gtpbuilder/';
        require $path."gtpbuilder.class.php";
        $this->helper = new gtpBuilder($this->modx);
        
        $this->addCss($this->helper->config['cssUrl'].'styles.css');
        $this->addLastJavascript($this->helper->config['jsUrl'].'gtpbuilder.js');
        $this->addHtml('<script type="text/javascript">
                            Ext.onReady(function(){
                                gtpBuilder.config = '.json_encode($this->helper->config).';
                            });
                        </script>');
       
        return parent::initialize();
    }
    
    public function getLanguageTopics() {
        return array('gtpbuilder:default');
    }
    
    public function checkPermissions() { return true;}
}

/**
 * Base CMP controller - triggers other controller through here
 */
class ControllerManagerController extends gtpBuilderManagerController {
    public static function getDefaultController() { return 'cmp'; }
 };// end class
 
