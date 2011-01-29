<?php
/**
 * @package Authentication
 */

/** defined constants returned by authentication actions **/

/** Authentication was successful */
define('AUTH_OK', 1); 

/** Authentication failed (invalid credentials) */
define('AUTH_FAILED', -1); // 

/** Authentication failed (user was not found) */
define('AUTH_USER_NOT_FOUND', -2); 

/** Authentication failed (User is inactive/disabled) */
define('AUTH_USER_DISABLED', -3);

/** Unknown server or i/o error */
define('AUTH_ERROR', -4); // 

require_once(LIB_DIR . "/User.php");
require_once(LIB_DIR . "/UserGroup.php");

/**
 * An abstract class that all authorities must inherit from. 
 * @package Authentication
 */
abstract class AuthenticationAuthority
{
    
    /** 
      * The tag used to identify this authority 
      * @var string
      */
    protected $AuthorityIndex; 

    /** 
      * The human readable title of this authority
      * @var string
      */
    protected $AuthorityTitle; 
    
    /** 
      * Image shown next to user name when logged in (optional) 
      * @var string
      */
    protected $AuthorityImage; 

    /** 
      * User Login type. One of 3 values: FORM, LINK or NONE
      * @var string
      */
    protected $userLogin;
    
    /**
     * Attempts to authenticate the user using the included credentials
     * @param string $login the userid to login (this will be blank for OAUTH based authorities)
     * @param string $password password (this will be blank for OAUTH based authorities)
     * @param User &$user This object is passed by reference and should be set to the logged in user upon sucesssful login
	 * @return int should return one of the AUTH_ constants     
     */
    abstract protected function auth($login, $password, &$user);
    
    /**
     * Retrieves a user object from this authority
     * @param string $login the userid to retrieve
	 * @return User a valid user object or false if the user could not be found
	 * @see User object
     */
    abstract public function getUser($login);

    /**
     * Retrieves a group object from this authority. Authorities which do not provide group information
     * should always return false
     * @param string $group the shortname of the group to retrieve
	 * @return UserGroup a valid group object or false if the group could not be found
	 * @see UserGroup object
     */
    abstract public function getGroup($group);

    /**
     * Initializes the authority objects based on an associative array of arguments
     * @param array $args an associate array of arguments. The argument list is dependent on the authority
     *
     * Required keys:
     * TITLE => The human readable title of the AuthorityImage
     * INDEX => The tag used to identify this authority @see AuthenticationAuthority::getAuthenticationAuthority
     * 
     * Optional keys:
     * LOGGEDIN_IMAGE_URL => a url to an image/badge that is placed next to the user name when logged in
     *
     * Specific authorities might have other required or optional keys
     * 
     * NOTE: Any subclass MUST call parent::init($args) to ensure proper operation
     *
     */
    public function init($args)
    {
        $args = is_array($args) ? $args : array();
        if (!isset($args['TITLE'], $args['INDEX'])) {
            throw new Exception("Title and index must be set");
        }
        
        $this->setAuthorityIndex($args['INDEX']);
        $this->setAuthorityTitle($args['TITLE']);

        if (!isset($args['USER_LOGIN'])) {
            throw new Exception("USER_LOGIN value not set for " . $this->AuthorityTitle);
        }

        if (!$this->setUserLogin($args['USER_LOGIN'])) {
            throw new Exception("Invalid USER_LOGIN setting for " . $this->AuthorityTitle);
        }
        
        
        if (isset($args['LOGGEDIN_IMAGE_URL']) && strlen($args['LOGGEDIN_IMAGE_URL'])) {
            $this->setAuthorityImage($args['LOGGEDIN_IMAGE_URL']);
        }
    }
    
    /**
      * Returns an array of valid user login types. Subclasses can override this to indicate valid
      * values
      * @return array a list of valid user login types
      */
    protected function validUserLogins()
    {
        return array('FORM', 'LINK', 'NONE');
    }
    
    /**
      * Sets the user login type
      * @param string userLogin a valid userLogin type (FORM, LINK, NONE)
      * @return boolean true if it was successful or false if it was not
      */
    public function setUserLogin($userLogin)
    {
        if (in_array($userLogin, $this->validUserLogins())) {
            $this->userLogin = strtoupper($userLogin);
            return true;
        }
        
        return false;
    }

    /**
      * Returns the user login type
      * @return string the user Login type
      */
    public function getUserLogin()
    {
        return $this->userLogin;
    }

    /**
     * Retrieves the authority index
     * @return string
    */
    public function getAuthorityIndex()
    {
        return $this->AuthorityIndex;
    }

    /**
     * Sets the authority index
     * @param string $index the authority index/tag
    */
    public function setAuthorityIndex($index)
    {
        $this->AuthorityIndex = (string) $index;
    }

    /**
     * Sets the authority title
     * @param string $title a human readable title
    */
    public function setAuthorityTitle($title)
    {
        $this->AuthorityTitle = (string) $title;
    }

    /**
     * Retrieves the authority title
     * @return string
    */
    public function getAuthorityTitle()
    {
        return $this->AuthorityTitle;
    }

    /**
     * Sets the authority image, an image that is shown next to the user when logged in. If an image is not present it will show the authority title
     * @param string a url (full or relative as appropriate) to a browser viewable image/badge. For best results use an image less than the text height of the footer content
    */
    public function setAuthorityImage($url)
    {
        $this->AuthorityImage = (string) $url;
    }

    /**
     * Retrieves the authority image
     * @return string
    */
    public function getAuthorityImage()
    {
        return $this->AuthorityImage;
    }
    
    /**
     * Returns the authentication config file
     * @return ConfigFile
    */
    private static function getAuthorityConfigFile()
    {
        return ConfigFile::factory('authentication', 'site');
    }

    /**
     * Parses the authentication config file and returns a list of authorities and their arguments
     * @return array
    */
    public static function getDefinedAuthenticationAuthorities()
    {
        static $configFile;
        if (!$configFile) {
            $configFile = self::getAuthorityConfigFile();
        }
        
        return $configFile->getSectionVars();
    }
    
    /**
     * Returns the default (i.e. the first) authentication authority in the config file. 
     * @return array 
    */
    public static function getDefaultAuthenticationAuthority()
    {
        $authorities = self::getDefinedAuthenticationAuthorities();
        return current($authorities);
    }

    public static function getDefaultAuthenticationAuthorityIndex()
    {
        $authorities = self::getDefinedAuthenticationAuthorities();
        return key($authorities);
    }

    /**
     * Retrieves an authentication authority by its index. This is the preferred way to retrieve an authority
     * @param string $index the index/tag of the authority to retrieve
     * @return AuthenticationAuthority object initialized based on the values in the authentication config file or false if the index was not found
    */
    public static function getAuthenticationAuthority($index)
    {
        static $configFile;
        if (!$configFile) {
            $configFile = self::getAuthorityConfigFile();
        }
        
        if ($authorityData = $configFile->getSection($index)) {
            $authorityClass = $authorityData['CONTROLLER_CLASS'];
            $authorityData['INDEX'] = $index;
            $authority = self::factory($authorityClass, $authorityData);
            return $authority;
        }
        
        return false;
    }
    
    /**
     * Retrieves a list of installed authorities based on available class files
     * Will search both the main lib dir as well as the site lib dir
     * @return an array of class names that inherit from AuthenticationAuthority
     *
     * Note: currently not used, but will likely be used in a future admin interface 
     */
    public static function getInstalledAuthentiationAuthorities()
    {
        $dirs = array(
            LIB_DIR, SITE_DIR . '/lib'
        );
        
        $authorities = array();
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $d = dir($dir);
                while (false !== ($entry = $d->read())) {
                    $file = $dir . '/' . $entry;
                    if (preg_match("/^([A-Z].*?)\.php$/", $entry, $bits)) {
                        $class = $bits[1];
                        if (@include_once($file)) {
                            if (class_exists($class) && is_subclass_of($class, 'AuthenticationAuthority')) {
                                $authorities[$class] = $class;
                            }
                        }
                    }
                }
            }
        }
                
        return $authorities;
    }
    
    /**
     * 
     * Initializes an authentication authority object
     * @param string $authorityClass the name of the class to instantiate. Must be a subclass of AuthenticationAuthority
     * @param array $args an associative array of arguments. Argument values depend on the authority
     * @return AuthenticationAuthority
     * @see AuthenticationAuthority::init()
     */
    public static function factory($authorityClass, $args)
    {
        if (!class_exists($authorityClass) || !is_subclass_of($authorityClass, 'AuthenticationAuthority')) {
            throw new Exception("Invalid authentication class $authorityClass");
        }
        $authority = new $authorityClass;
        $authority->init($args);
        return $authority;
    }

    /**
     * 
     * Resets the authority and returns it to a fresh state.
     * Called by the logout method to clean up any authority specific data (caches etc). Not all authorities will need this
     */
    protected function reset()
    {
    }
    
    /**
     * Logout the current user
     * @param Module $module the module initiating the logout
     * 
     * Subclasses should not need to override this, but instead provide additional behavior in reset()
     */
    public function logout(Module $module)
    {
        $session = $module->getSession();
        $session->logout();
        $this->reset();
    }
    
    /**
     * Login a user based on supplied credentials
     * @param string $login 
     * @param string $password
     * @param Module $module 
     * @see AuthenticationAuthority::reset()
     * 
     * Subclasses should not need to override this, but instead provide additional behavior in reset()
     */
    public function login($login, $password, Module $module)
    {
        $result = $this->auth($login, $password, $user);
        
        if ($result == AUTH_OK) {
            $session = $module->getSession();
            $session->login($user);
        }
        
        return $result;
    }
}
