<?php
namespace Tools;

/**
 * 常驻进程
 */
class Deamon{
    protected $_pidFile;
    public function __construct($pidFile){
        $this->_pidFile = $pidFile;
        $this->_checkPcntl();
    }

    /**
     * 创建守护进程核心函数
     * @return string|void
     */
    private function _demonize(){
        if (php_sapi_name() != 'cli') {
            die('Should run in CLI');
        }
        //创建子进程
        $pid = pcntl_fork();
        if ($pid == -1) {
            return 'fork faile';
        } elseif ($pid) {
            //终止父进程
            exit('parent process');
        }
        //在子进程中创建新的会话
        if (posix_setsid() === -1) {
            die('Could not detach');
        }
        //重设文件创建的掩码
        umask(0);
        $fp = fopen($this->_pidFile, 'w') or die("Can't create pid file");
        //把当前进程的id写入到文件中
        fwrite($fp, posix_getpid());
        fclose($fp);
        //关闭文件描述符
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        //运行守护进程的逻辑
        $this->job();
        return;
    }

    /**
     * 守护进程的任务
     */
    private function job(){
    }

    /**
     * 获取守护进程的id
     * @return int
     */
    private function _getPid(){
        //判断存放守护进程id的文件是否存在
        if (!file_exists($this->_pidFile)) {
            return 0;
        }
        $pid = intval(file_get_contents($this->_pidFile));
        if (posix_kill($pid, SIG_DFL)) {//判断该进程是否正常运行中
            return $pid;
        } else {
            unlink($this->_pidFile);
            return 0;
        }
    }

    /**
     * 判断pcntl拓展
     */
    private function _checkPcntl(){
        !function_exists('pcntl_signal') && die('Error:Need PHP Pcntl extension!');
    }

    private function _message($message){
        printf("%s  %d %d  %s" . PHP_EOL, date("Y-m-d H:i:s"), posix_getpid(), posix_getppid(), $message);
    }

    /**
     * 开启守护进程
     */
    private function start(){
        if ($this->_getPid() > 0) {
            $this->_message('Running');
        } else {
            $this->_demonize();
            $this->_message('Start');
        }
    }

    /**
     * 停止守护进程
     */
    private function stop(){
        $pid = $this->_getPid();
        if ($pid > 0) {
            //通过向进程id发送终止信号来停止进程
            posix_kill($pid, SIGTERM);
            unlink($this->_pidFile);
            echo 'Stoped' . PHP_EOL;
        } else {
            echo "Not Running" . PHP_EOL;
        }
    }

    private function status(){
        if ($this->_getPid() > 0) {
            $this->_message('Is Running');
        } else {
            echo 'Not Running' . PHP_EOL;
        }
    }

    public function run($argv){
        $param = is_array($argv) && count($argv) == 2 ? $argv[1] : null;
        switch ($param) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'status':
                $this->status();
                break;
            default:
                echo "Argv start|stop|status " . PHP_EOL;
                break;
        }
    }
}
$deamon = new Deamon(dirname(__FILE__) . '/cache/report.log');
$deamon->run($argv);