<?php
/**
 * Description of GitHub
 *
 * @author alan
 */
class GitHub {
    
    const baseURL = 'https://api.github.com';
    
    
    public function setCredentials($username,$password){
        $this->creds = "$username:$password";
    }
    
    public function getLatestCommitId($owner,$repo,$branch='master'){
        // Grab branches for info
        if(!$branches = self::getBranches($owner,$repo)){
            throw new GitHubException('Failed to retreive branch list');
            return;
        };
        dump($branches);
        foreach($branches as $brnch){
            if( $brnch->name != $branch){ continue; };
            return $brnch->commit->sha;
        };
        return false;
    }//
    
    
    public function getCommitSourceZip($owner,$repo,$sha,$format='zipball'){
        $zipUrl = "/repos/$owner/$repo/$format/$sha";
        $zipFileContents = self::fetch($zipUrl,false);
        return $zipFileContents;
    }
    
    /**
     * Get information about a repo
     * @param string $owner Repo Owner
     * @param string $repo Repo Name
     * @return object|false
     */
    public function getRepo($owner,$repo){
        // Construct API url
        $url = "/repos/$owner/$repo";
        try{ return self::fetch($url);
        } catch( GitHubException $E){ return false; };
    }//
    
    /**
     * Get list of repo branches
     * @param type $owner
     * @param type $repo
     * @return array|false
     */
    public function getBranches($owner,$repo){
        // Construct API url
        $url = "/repos/$owner/$repo/branches";
        try{ $data = self::fetch($url,false);
        } catch( GitHubException $E){ return false; };
        return $data;
    }//
    
    /**
     * Retrieve a response from Github json api
     * @static
     * @param string $url
     * @return mixed Response
     */
    private static function fetch( $url, $asJSON=true ){
        // Check php has been compiled with cURL
        if(!function_exists('curl_version')){
            throw new GitHubException('cURL extension not found'); }//
        // Make request using cURL
        $url = self::baseURL.str_replace(self::baseURL,'',$url);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true); 
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $content = curl_exec($ch);
        curl_close($ch);
        if(!$asJSON){ 
            return $content;
        };
        
        // Parse json
        $data = json_decode($content);
        if(is_null($data)){ throw new GitHubException('Invalid JSON response');};
        // Return response object
        return $data;
    }//
    
};// end class GitHub


class GitHubException extends Exception {};
