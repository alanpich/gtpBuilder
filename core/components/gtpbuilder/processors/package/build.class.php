<?php
class gtpBuilderTransportPackageBuildProcessor extends modProcessor {
    
    public $languageTopics = array('gtpbuilder:default');
    
    public function process(){
        $this->helper = gtpBuilder::getInstance();
        $params = $this->getProperties();
        
        // Gather details
        $owner = $this->getProperty('owner',false);
        if(!$owner){
            $this->addFieldError('owner',$this->modx->lexicon('gtpbuilder.repo_owner_ns'));
            return $this->failure($this->modx->lexicon('gtpbuilder.repo_owner_ns'));
        }
        $repo = $this->getProperty('repo',false);
        if(!$repo){
            $this->addFieldError('repo',$this->modx->lexicon('gtpbuilder.repo_ns'));
            return $this->failure($this->modx->lexicon('gtpbuilder.repo_ns'));
        }
        $branch = $this->getProperty('branch',false);
        if(!$branch){
            $this->addFieldError('branch',$this->modx->lexicon('gtpbuilder.repo_branch_ns'));
            return $this->failure($this->modx->lexicon('gtpbuilder.repo_branch_ns'));
        }
        
        $username = $this->getProperty('auth_user',false);
        if($username!==false){
            $password = $this->getProperty('auth_pass',false);
            if(!$password){
                $this->addFieldError('auth_pass',$this->modx->lexicon('gtpbuilder.auth_pass_ns'));
                return $this->failure($this->modx->lexicon('gtpbuilder.auth_pass_ns'));
            }
        };
        
        // Run the builder!
        if($username != false && !empty($username)){
            $this->helper->setGithubCredentials($username,$password);
        };
        $this->helper->buildFromGithub($owner,$repo,$branch);        
        
        $this->helper->log('COMPLETE');
        
        return $this->success();
    }//
    
    
};// end class gtpBuilderTransportPackageBuildProcessor
return 'gtpBuilderTransportPackageBuildProcessor';
