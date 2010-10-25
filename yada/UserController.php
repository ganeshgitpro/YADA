<?php

require_once('php/Builder.php');

/**
 * Provides the methods for user interface handling of the users and the logs.
 */
class UserController {

    protected static $instance;

    private function __construct() {

    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserController();
        }
        return self::$instance;
    }

    public function login() {
        if (SessionManager::getInstance()->isLoggedIn()) {
            Utils::getInstance()->redirect('index.php?user=profile');
            return;
        }

        $username = '';
        if ($_POST) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($username != '' && $this->userExists($username)) {

                $udao = new UserDAO();
                $user = $udao->getUser($username);
                if ($password != $user->getPassword()) {
                    SessionManager::getInstance()->error('Password invalid!');
                } else {
                    SessionManager::getInstance()->setUser($user);
                    Utils::getInstance()->redirect('index.php?user=profile');
                    return;
                }
            } else {

                SessionManager::getInstance()->error("Dude, you're doing it wrong!");
            }
        }

        //also load the food data
        $foodCtrl = FoodController::getInstance();

        include 'views/login.php';
    }

    public function register() {
        if (SessionManager::getInstance()->isLoggedIn()) {
            Utils::getInstance()->redirect('index.php?user=profile');
            return;
        }
        $username = '';

        if ($_POST) {

            $username = $_POST['username'];
            $password = $_POST['password'];

            $userExists = $this->userExists($username);

            if ($userExists) {
                SessionManager::getInstance()->error('The username you chose <b>'
                        . $username . '</b> already exists! Please type another one.');
            } else {

                // Registers a new user.
                $userDir = DATA . $username . '/';
                $success = mkdir($userDir, 0777);
                if (true || $success) {
                    $user = new User();
                    $user->setUsername($username);
                    $user->setPassword($password);

                    $udao = new UserDAO();
                    $udao->save($user);

                    SessionManager::getInstance()->setUser($user);
                    SessionManager::getInstance()->info('Welcome to YADA, ' . $user->getUsername());

                    Utils::getInstance()->redirect('index.php?user=profile');
                    return;
                } else {
                    SessionManager::getInstance()->error('An error ocurred. Please contact support.');
                }
            }
        }
        include 'views/register.php';
    }

    public function profile() {
        $user = SessionManager::getInstance()->getUser();
        if ($_POST) {
            $udao = new UserDAO();
            $user->setFirstname($_POST['firstname']);
            $user->setLastname($_POST['lastname']);
            $user->setAge($_POST['age']);
            $user->setWeight($_POST['weight']);
            $user->setHeight($_POST['height']);
            $user->setActivityLevel($_POST['activity_level']);
            $user->setGender($_POST['gender']);
            $user->setCalculatorId($_POST['calculator_id']);
            $udao->save($user);

            SessionManager::getInstance()->info('Your profile has been updated.');
        }
        include 'views/profile.php';
    }

    public function changeCalculator() {

    }

    public function calendar() {
        include 'views/foodLog.php';
    }

    public function welcome() {
        include 'views/welcome.php';
    }

    public function today() {
        $date = '';
        $log = NULL;
        $userDao = new UserDAO();

        $sessMgr = SessionManager::getInstance();
        $user = $sessMgr->getUser();
        $foodData = $sessMgr->getFoodData();

        if (isset($_GET['for']) && $_GET['for'] != '') {
            $date = $_GET['for'];
            $log = $userDao->getLogByDate($user->getUsername(), $date, $foodData);
            
            include 'views/editLog.php';
        } else {
            $arrLogs = $userDao->getAllLog($user->getUsername(), $foodData);

            include 'views/dailyLog.php';
        }
    }

    public function deletelog() {
        $sessMgr = SessionManager::getInstance();
        $user = $sessMgr->getUser();
        $foodData = $sessMgr->getFoodData();

        if (isset($_GET['for']) && $_GET['for'] != '') {
            $date = $_GET['for'];
        }

        if (isset($_GET['del']) && $_GET['del'] != '') {
            $consumpFoodId = $_GET['del'];
        }
        if (isset($date) && isset($consumpFoodId)) {
            $userDao = new UserDAO();
            $userDao->delConsumption($date, $consumpFoodId, $user->getUsername(), $foodData);
        }
    }

    public function log() {
        if (SessionManager::getInstance()->getFoodData() == null)
            FoodController::populateFoodData();
        if (!empty($_POST['addLogEntry'])) {
            self::addLogEntry();
        }
        include 'views/logEntry.php';
    }

    public static function addLogEntry() {
        $l = new Log();
        if (!empty($_POST['logDate']))
            $l->setDate($_POST['logDate']);
        else
            $l->setDate(date('Y-m-d'));
        $arrConsumptions = array();
        $maxIndex = (int) $_POST['maxIndex'];
        $foodData = SessionManager::getInstance()->getFoodData();
        for ($i = 0; $i < $maxIndex; $i++) {
            if (!empty($_POST['id' . ($i + 1)])) {
                $f = FoodData::findFood($foodData, $_POST['id' . ($i + 1)]);
                if ($f != null) {
                    if (!empty($_POST['servings' . ($i + 1)])) {
                        $consum = new Consumption();
                        $consum->setFood($f);
                        $consum->setQuantity($_POST['servings' . ($i + 1)]);
                        array_push($arrConsumptions, $consum);
                    }
                }
            }
        }
        $l->setConsumption($arrConsumptions);
        $dao = new UserDAO();
        $dao->saveLog(SessionManager::getInstance()->getUser()->getUsername(), $l);
    }

    public function saveLog() {
        // TODO: save
    }

    public function logout() {
        session_destroy();

        Utils::getInstance()->redirect('index.php?user=login');
    }

    /**
     * checks if the user already exists
     * @param <type> $username
     * @return boolean
     */
    protected function userExists($username) {
        return (bool) realpath('./data/' . $username);
    }

    public function getDelURI($id, $date) {
        return '?user=deletelog&for=' . $date . '&del=' . $id;
    }

}

?>
