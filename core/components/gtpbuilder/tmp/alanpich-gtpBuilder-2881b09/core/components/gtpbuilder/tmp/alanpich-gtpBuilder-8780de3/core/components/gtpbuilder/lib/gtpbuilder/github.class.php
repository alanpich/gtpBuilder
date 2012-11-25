<?php
/**
 * Description of github
 *
 * @author Alan Pich <alan@alanpich.com>
 * @copyright (c) 2013, Alan Pich
 * @date 25-Nov-2012
 */
class GithubRepo 
extends gtpRepo
implements gtpRepoInterface {
    
    const baseUrl = 'https://api.github.com';
    const sourceName = "GITHUB";
    
    protected static $_instance = null;
    
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
           return false;
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
    
    
    public function setAuthCredentials($username, $password) {
        
    }//
    
    /**
     * Download a zipball from github and extract it
     * @param string $owner Repo Owner
     * @param string $repo  Repo name
     * @param string $sha   SHA commit id
     * @param string $format either 'zipball' or 'tarball'
     * @return string|false Path to extracted repo
     */
    public function downloadArchive($owner, $repo, $sha, $format='zipball') {
        $this->log("Downloading archive...");
        try{
            $data = self::fetch("/repos/$owner/$repo/$format/$sha",false);
        } catch (Exception $E){
            $this->warn(self::sourceName.": ".$E->getMessage()); return false;
        };
        
        // Generate a savepath directory
        $savePath = $this->gtp->config['tmp_path'];
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
            if(!class_exists('ZipArchive')){
                $this->error('ZipArchive class not found');
                return false;
            }
            $zip = new ZipArchive();
            if($zip->open($filePath)===TRUE){
                $zip->extractTo($savePath);
                $zip->close();
            } else {
                $this->error('Unable to open file archive');
                return false;
            }
        } else {
            $this->error('Invalid archive format');
            return false;
        }        
        
        // Remove zip file
        unlink($filePath);
        
        // Work out subfolder name
        $subfolder = "$owner-$repo-".substr($sha,0,7)."/";
        return $savePath.$subfolder;        
    }//
    
    public function extractArchive($pathToArchive) {
        
    }
    
    
    
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
        } catch( Exception $E){ $this->warn(self::sourceName.": ".$E->getMessage()); return false; };
        return $data;
    }//
    
}; // end class github