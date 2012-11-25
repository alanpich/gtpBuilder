<?php
/**
 * Description of gtpbuilder
 *
 * @author alan
 */
class gtpBuilder {
    
    /* @var string cURL basic auth string */
    private $githubBasicAuth = '';
    const githubBaseUrl = 'https://api.github.com';
    
    private static $_instance = null;
    
    /**
     * Factory class instantiator - use this to load instances
     * @param modX $modx
     * @return gtpBuilder instance
     */
    public static function getInstance(){
        global $modx;
        if(self::$_instance == null){
            self::$_instance = new self($modx);
        };
        return self::$_instance;
    }//
    
    
    
    function __construct(modX &$modx){
        $this->modx =& $modx;
        $this->loadConfig();
        $this->loadServices();  
    }//
    
    
    /**
     * Setup config params
     */
    private function loadConfig(){
        $core = dirname(__FILE__).'/';
        $assets = $this->modx->getOption('assets_url').'components/gtpbuilder/';
        $this->config = array(
                'core_path' => $core,
                'lib_path' => $core.'lib/',
                'tmp_path' => $core.'tmp/',
                'tpl_path' => $core.'templates/',
                'assetsUrl' => $assets,
                'cssUrl' => $assets.'mgr/css/',
                'jsUrl' => $assets.'mgr/js/',
                'connectorUrl' => $assets.'mgr/connector.php'
            );
    }//
    
    /**
     * Load required services
     */
    private function loadServices(){
    }
    

    
    /**
     * Build a transport package from source folder
     * @param string $path Path to extracted repo
     * @return boolean success
     */
    public function buildFromArchive($path){
        
        // Check for a _build directory
        $buildPath = $path.'_build/';
        $this->log("Preparing to build");
        if(!is_dir($buildPath)){ 
            $this->error("Repo does not include a _build directory");
            throw new Exception('Repo does not include a _build directory'); return false;
        };
        
        // Check for a build.transport.php in _build
        $this->log("Checking for build script");
        $buildScript = $buildPath.'build.transport.php';
        if(!file_exists($buildScript)){
            $this->error("Unable to locate build.transport.php");
            throw new Exception('Unable to locate build.transport.php'); return false;
        }
        // Check build scripts is readable
        if(!is_readable($buildScript)){
            $this->error("Unable to read build.transport.php");
            throw new Exception('Build script is not readable'); return false;
        }
        
        // Run the build scripts
        $this->log("Running package build script...");
        define('LOG_TARGET','FILE');
        $output = @ include $buildScript;
        $this->log("Build completed");
        
        // Remove archive folder
        $this->log("Removing temporary files & cleaning up");
        rrmdir($path);
        
        return true;
    }//
    
    

    

    
  
    /**
     * Log an INFO msg to modx error log
     * @param string $msg
     */
    public function log($msg){$this->modx->log(modX::LOG_LEVEL_INFO,$msg); }
    public function warn($msg){$this->modx->log(modX::LOG_LEVEL_WARN,$msg); }
    public function error($msg){$this->modx->log(modX::LOG_LEVEL_ERROR,$msg); }
    
    
    
    public static function buildFromGithub($source,$owner,$repo,$branch='master',$username='',$password=''){
        // Grab instance of self
        $gtp = self::getInstance();
        $gtp->log("Attempting to build Transport Package from remote repo");       
        
        // Check valid source type
        $sourceFile = $gtp->config['lib_path'].'gtpbuilder/'.strtolower($source).'.class.php';
        if(!file_exists($sourceFile)){
            $gtp->error("Invalid repo source [".$source."]"); return false;
        };
        
        // Load source class
        require_once $gtp->config['lib_path'].'gtpbuilder/gtprepo.interface.php';
        require_once $sourceFile;
        
        // Check source class valid
        $className = $source."Repo";
        if(!class_exists($className)){
            $gtp->error("Invalid repo source class [".$source."Repo]"); return false;
        };
        

        $gtp->log("Repo source selected: $source");
        $gtp->log("Repo selected: $owner/$repo:$branch");
        
        // Initialize source
        $remote = $className::getInstance();

        // Set auth details
        if(!empty($username)){
            $remote->auth_user = $username;
            $remote->auth_pass = $password;
            $gtp->log("Authenticating with user $username");
        }
        
        // Grab latest commit ID
        if(!$sha = $remote->getLatestCommitId($owner,$repo,$branch)){
            return false;
        };
        
        // Download and extract source archive
        if(!$path = $remote->downloadArchive($owner,$repo,$sha)){
            return false;
        };
        
        // Build TP from archive (and remove source)
        $success = $gtp->buildFromArchive($path);
        
        return $success;
    }//
    
    
};// end class gtpbuilder




if(!function_exists('dump')){
function dump($mxd){ echo '<pre>'.print_r($mxd,1).'</pre>'; };
};

if(!function_exists('rrmdir')){
    function rrmdir($dir) {
      if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
          if ($object != "." && $object != "..") {
            if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
          }
        }
        reset($objects);
        rmdir($dir);
      }
    };
};