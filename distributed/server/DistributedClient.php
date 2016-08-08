<?php
//namespace server;
//use Yaf;
class DistributedClient
{
	public $application;
    public static $instance;
    public $c_client_pool=[];
    public $b_client_pool=[];
	public function __construct() {
		define('APPLICATION_PATH', dirname(dirname(dirname(__DIR__))). "/application");
		$this->application = new Yaf_Application(dirname(APPLICATION_PATH). "/conf/application.ini");
		$this->application->bootstrap();
	}

	 public function addServerClient($address)
    {
       	$client = new swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        $client->on('Connect', array(&$this, 'onConnect'));
        $client->on('Receive', array(&$this, 'onReceive'));
        $client->on('Close', array(&$this, 'onClose'));
        $client->on('Error', array(&$this, 'onError'));
        $client->connect($address,9504);
        $this->c_client_pool[ip2long($address)] = $client;
        $this->b_client_pool[ip2long($address)] = $client;
    }

    public function onConnect($serv) {
        $localinfo=swoole_get_local_ip();
        foreach ($this->b_client_pool as $k => $v) {
            $v->send(json_encode(array('code' =>10001,'status'=>1,'fd'=>$localinfo['eth0'])));
            unset($this->b_client_pool[$k]);
        }
        //print_r($this->c_client_pool);
        //$serv->send(json_encode(array('code' =>10001,'status'=>1,'fd'=>$localinfo['eth0'])));
    }

	public function onReceive($serv, $fd, $from_id, $data) {
		/*$remote_info=json_decode($data, true);
        if($remote_info['code']==10002){
            $this->c_client_pool[ip2long($remote_info['fd'])]= array('fd' =>$fd,'client'=>$client);

        }*/
        //$remote_info=json_decode($data, true)
        // start a task
        //$serv->task(json_encode($param));
	}
	public function onTask($serv, $task_id, $from_id, $data) {
        $fd = json_decode($data, true);
        $tmp_data=$fd['data'];
        $this->application->execute(array('swoole_task','demcode'),$tmp_data);
        $serv->send($fd['fd'] , "Data in Task {$task_id}");
        return  'ok';
	}
	public function onFinish($serv, $task_id, $data) {
		echo "Task {$task_id} finish\n";
        echo "Result: {$data}\n";
	}
	 /**
     * 服务器断开连接
     * @param $cli
     */
    public function onClose($serv,$fd)
    {
        print_r("close\n");
        $serv->close();
        /*foreach ($this->c_client_pool as $k => $v) {
            if($v['fd']==$fd){
                unset($this->c_client_pool[$k]);
            }
        }*/
        //unset($this->server_clients[ip2long($cli->address)]);
        unset($serv);
    }
    /**
     * 服务器连接失败
     * @param $cli
     */
    public function onError($cli)
    {
        print_r("close\n");
        $cli->close();
        //unset($this->server_clients[$cli->address]);
        unset($cli);
    }
    public function getserlist($keyname='Distributed'){
                ob_start();
                distributed_dredis::getInstance()->getfd($keyname);
                $result = ob_get_contents();
                ob_end_clean();
                return $result;
    }

    public function appendserlist($data,$keyname='Distributed'){
        distributed_dredis::getInstance()->savefd($data,$keyname);
    }
    public static function getInstance() {
        if (!(self::$instance instanceof DistributedClient)) {
            self::$instance = new DistributedClient;
        }
        return self::$instance;
    }
}

