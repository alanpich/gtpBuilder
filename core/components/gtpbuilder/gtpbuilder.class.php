<?php
/**
 * Description of gtpbuilder
 *
 * @author alan
 */
class gtpBuilder {
    
    /* @var string cURL basic auth string */
    private $githubBasicAuth = '';
    
    private static $_instance = null;
    const githubBaseUrl = 'https://api.github.com';
    
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
     * Set github api auth credentials
     * @param string $username
     * @param string $password
     * @return void;
     */
    public function setGithubCredentials($username,$password){
        $this->githubBasicAuth = "$username:$password";
    }
    
    /**
     * Download a zipball from github and extract it
     * @param string $owner Repo Owner
     * @param string $repo  Repo name
     * @param string $sha   SHA commit id
     * @param string $format either 'zipball' or 'tarball'
     * @return string|false Path to extracted repo
     */
    public function downloadZipball($owner,$repo,$sha,$format='zipball'){
        // Grab the data
        $this->log('Downloading source...');
        $data = self::fetch("/repos/$owner/$repo/$format/$sha",false);
        // Generate a savepath directory
        $savePath = $this->config['tmp_path']."$owner/";
        if(!is_dir($savePath)){ mkdir($savePath,0700,true); };
        // Save the file
        $filePath = $savePath."$repo.zip";
        if(!file_put_contents($filePath,$data)){
            $this->error("Unable to write file to $filePath");
            return;
        };

        // Extract archive
        $this->log("Extracting archive...");
        if($format == 'zipball'){
            $zip = new ZipArchive();
            if($zip->open($filePath)===TRUE){
                $zip->extractTo($savePath);
                $zip->close();
            } else {
                $this->error('Unable to open file archive');
            }
        } else {
            dump('invalid format chosen');
        }
        
        // Remove zip file
        $this->log("Removing archive...");
        unlink($filePath);
        
        // Work out subfolder name
        $subfolder = "$owner-$repo-".substr($sha,0,7)."/";
        return $savePath.$subfolder;
    }//
    
    
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
            throw new Exception('Repo does not include a _build directory'); return;
        };
        
        
        // Check for a build.transport.php in _build
        $this->log("Checking for build script");
        $buildScript = $buildPath.'build.transport.php';
        if(!file_exists($buildScript)){
            $this->error("Unable to locate build.transport.php");
            throw new Exception('Unable to locate build.transport.php'); return;
        }
        // Check build scripts is readable
        if(!is_readable($buildScript)){
            $this->error("Unable to read build.transport.php");
            throw new Exception('Build script is not readable'); return;
        }
        
        // Run the build scripts
        $this->log("Running package build script...");
        ob_start();
        @ include $buildScript;
        $output = ob_get_clean();
        $this->log("Build completed");
        
        // Remove archive folder
        $this->log("Removing temporary files & cleaning up");
        rrmdir($path);
        
        return true;
    }//
    
    
    
    
    /**
     * Get the SHA id of the latest commit to a repo
     * @param string $owner
     * @param string $repo
     * @param string $branch
     * @return string|false SHA id
     * @throws Exception
     */
    public function getLatestCommitId($owner,$repo,$branch='master'){
        $this->log("Checking for most recent commit on $owner/$repo:$branch");
        // Grab branches for info
        if(!$branches = self::getBranches($owner,$repo)){
            $this->error("Failed to find branches for $owner/$repo");
            throw new Exception('Failed to retreive branch list');
            return;
        };
        if(isset($branches->message)){
            $this->warn("GitHub API says '".$branches->message."'");
            return false;
        }
        foreach($branches as $brnch){
            if( $brnch->name != $branch){ continue; };
            $this->log("Latest commit ID: ".$brnch->commit->sha);
            return $brnch->commit->sha;
        };
        $this->error("Requested branch [$branch] not found in $owner/$repo");
        return false;
    }//
    
    
    /**
     * Get a list of branches for a github repo
     * @param type $owner
     * @param type $repo
     * @return boolean
     */
    public function getBranches($owner,$repo){
        // Construct API url
        $url = "/repos/$owner/$repo/branches";
        try{ $data = self::fetch($url);
        } catch( Exception $E){ return false; };
        return $data;
    }//
    
    
    /**
     * Make a cURL request and return data
     * @param string $url URL to request
     * @param boolean $parseJSON Attempt to parse as json
     * @return mixed
     */
    private static function fetch($url,$parseJSON = true){
        $self = self::getInstance();
        // Prepare url
        $url = self::githubBaseUrl.str_replace(self::githubBaseUrl,'',$url);
        // Check php has been compiled with cURL
        if(!function_exists('curl_version')){
            throw new Exception('cURL extension not found'); }//
        // Make request using cURL
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if(!empty($self->githubBasicAuth)){            
            curl_setopt($ch, CURLOPT_USERPWD, $self->githubBasicAuth);
        }
        $content = curl_exec($ch);
        curl_close($ch);
        if($parseJSON!==true){ 
            return $content;
        };
        
        // Parse json
        $data = json_decode($content);
        if(is_null($data)){ throw new Exception('Invalid JSON response');};
        // Return response object
        return $data;
    }
    
    
    /**
     * Log an INFO msg to modx error log
     * @param string $msg
     */
    private function log($msg){$this->modx->log(modX::LOG_LEVEL_INFO,$msg); }
    private function warn($msg){$this->modx->log(modX::LOG_LEVEL_WARN,$msg); }
    private function error($msg){$this->modx->log(modX::LOG_LEVEL_ERROR,$msg); }
    
    
    
    public static function buildFromGithub($owner,$repo,$branch){
        $gtp = self::getInstance();
        $gtp->log("Attempting to build from repo $owner/$repo:$branch");
        if(!$sha = $gtp->getLatestCommitId($owner,$repo,$branch)){
            return false;
        };
        
        if(!$path = $gtp->downloadZipball($owner,$repo,$sha)){
            return false;
        };
        $success = $gtp->buildFromArchive($path);
        $gtp->log("COMPLETE");
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