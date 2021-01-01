<?php

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use CharlotteDunois\Yasmin\Client;
use CharlotteDunois\Yasmin\Models\User;
use CharlotteDunois\Yasmin\Models\Message;
use CharlotteDunois\Yasmin\Models\Guild;
use CharlotteDunois\Yasmin\Models\DMChannel;
require_once \dirname(__FILE__) . "/vendor/autoload.php";
require_once \dirname(__FILE__) . "/vendor2/autoload.php";

final class App{
	/** @var string */
	private static $CONFIG_PATH = "%currentDir%bot_config.json";
	/** @var LoopInterface */
	private $loop;
	
	/** @var Client */
	private $client;
	/** @var resource */
	public $pointer;
	/** @var string[] */
	public $cache = [];
	
	final function setup() : void{
		$this->parseConfiguration();
		$this->loop = Factory::create();
		$this->client = new Client([], $this->loop);
		echo "Logging in to Discord..." . PHP_EOL;
		var_dump(__DIR__);
		var_dump($this->config);
		$this->client->login($this->config->discord_bot_token)->done(function(){
			echo "Logged in to Discord." . PHP_EOL;
		});
		if(!file_exists($this->config->file)){
			throw new \RuntimeException("The target file does not exist");
		}
		$this->client->on("debug", function($m){
			echo $m . PHP_EOL;
		});
		$this->pointer = fopen($this->config->file, "r");
		$this->setupTimers();
		$this->setupPnctlSignals();
	}
	
	final private function parseConfiguration() : void{
		$path = str_replace("%currentDir%", __DIR__ . "/", self::$CONFIG_PATH);
		if(!file_exists($path)){
			throw new \RuntimeException("Configuration file not found at " . $path);
		}
		$this->config = (object) \json_decode(\file_get_contents($path));
		var_dump(json_last_error());
	}
	
	final private function setupPnctlSignals() : void{
		static $callable = "onShutdownCallback";
		/*$this->loop->addSignal(\SIGINT, $callable);
		$this->loop->addSignal(\SIGHUP, $callable);
		$this->loop->addSignal(\SIGTERM, $callable);*/
		$this->loop->run();
	}
	
	final private function setupTimers() : void{
		
		$this->loop->addTimer(6, function() : void{
			
			$this->loop->addPeriodicTimer(0.2, function() : void{
				
				$cache = explode("\n", file_get_contents($this->config->file));
					$send = [];
				foreach($cache as $i => $line){
					if($line !== ($this->cache[$i] ?? "")){
						$this->cache[$i] = $line;
						$send[] = $line;
					}
				}
				$arr = str_split(implode(PHP_EOL, $send), 1800);
				foreach($this->client->guilds as $guild){
					$channel = $guild->channels->get($this->config->channel);
					if($channel !== \NULL){
						foreach($arr as $str){
							if(empty(trim($str))){
								continue;
							}
							$channel->send($str)->done();
						}
					}
				}
			});
		});
	}
	
}
$app = new App();
$app->setup();
function onShutdownCallback() : void{
	global $app;
	$app->loop->stop();
	@fclose($app->pointer);
	echo "Saved cache" . PHP_EOL;
	exit(0);
}

echo "ewo\n";