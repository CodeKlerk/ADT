<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Recover extends MY_Controller {
	var $backup_dir = "./backup_db";
	function __construct() {
		parent::__construct();
	}

	public function index() {
		$data['backup_files'] = $this -> checkdir();
		$data['active_menu'] = 1;
		$data['content_view'] = "recover/test_v";
		$data['title'] = "Dashboard | System Recovery";

		$CI = &get_instance();
		$CI -> load -> database();
		$data['sys_hostname'] = explode(':', $CI->db->hostname)[0];
		$data['sys_hostport']  = (isset($CI->db->port)) ? $CI->db->port : 3306 ;
		$data['sys_username'] = $CI->db->username;
		$data['sys_password'] = $CI->db->password;


		$this -> template($data);
	}

	public function check_server() {
		$host_name = ($this -> input -> post("inputHost")!= null) ? $this -> input -> post("inputHost") : 'localhost';
		$host_user = $this -> input -> post("inputUser");
		$host_password = $this -> input -> post("inputPassword");
		$host_port = $this -> input -> post("inputPort");

		$link = @mysql_connect($host_name.':'.$host_port, $host_user, $host_password);
		if ($link == false) {
			$status = 0;
		} else {
			$status = 1;
			$this -> session -> set_userdata("db_host", $host_name);
			$this -> session -> set_userdata("db_user", $host_user);
			$this -> session -> set_userdata("db_pass", $host_password);
			$this -> session -> set_userdata("db_port", $host_port);

		}
		echo $status;
	}

	public function check_database() {
		$host_name = $this -> session -> userdata("db_host");
		$host_user = $this -> session -> userdata("db_user");
		$host_password = $this -> session -> userdata("db_pass");
		$database_name = $this -> input -> post("inputDb");
		$database_port = $this -> input -> post("inputport");

		$link = @mysql_connect($host_name, $host_user, $host_password);
		$db_selected = @mysql_select_db($database_name, $link);
		if (!$db_selected) {
			$status = "\nConnection Success!\nDatabase does not exist!";
			$sql = "CREATE DATABASE $database_name";
			if (@mysql_query($sql, $link)) {
				$status .= "\nDatabase created successfully!";
				$this -> session -> set_userdata("db_name", $database_name);
			} else {
				$status = 0;
			}
		} else {
			$status = "\nConnection Success!\nDatabase Exists!";
			$this -> session -> set_userdata("db_name", $database_name);
		}			
		echo $status;
	}

	public function start_database() {
		error_reporting(E_ALL | E_STRICT);
		$this->load->library('UploadHandler');
		$upload_handler = new UploadHandler();
	}

	public function checkdir() {
		$dir = $this -> backup_dir;
		$backup_files = array();
		$backup_headings = array('Filename', 'Options');
		$options = '<button class="btn btn-primary btn-sm recover" >Recover</button>';

		if (is_dir($dir)) {
			$files = scandir($dir, 1);
			foreach ($files as $object) {
				if ($object != "." && $object != "..") {
					$backup_files[] = $object;
				}
			}
		} else {
			mkdir($dir);
		}
		$this -> load -> module('table');
		return $this -> table -> load_table($backup_headings, $backup_files, $options);
	}

	public function showdir() {
		$dir = $this -> backup_dir;
		$backup_files = array();
		$backup_headings = array('Filename', 'Options');
		$options = '<button class="btn btn-primary btn-sm recover" >Recover</button>';

		if (is_dir($dir)) {
			$files = scandir($dir, 1);
			foreach ($files as $object) {
				if ($object != "." && $object != "..") {
					$backup_files[] = $object;
				}
			}
		} else {
			mkdir($dir);
		}
		$this -> load -> module('table');
		echo $this -> table -> load_table($backup_headings, $backup_files, $options);
	}

	public function start_recovery() {
		$file_name =$_POST['file_name'];
		$file_path =  FCPATH.'backup_db/'.$file_name;
		$unzip = $this -> uncompress_zip($file_path);;
		$file_path = str_replace(".zip", "", $file_path);

		$CI = &get_instance();
		$CI -> load -> database();
		$hostname = $this -> session -> userdata("db_host");
		$port = $this -> session -> userdata("db_port");
		$username = $this -> session -> userdata("db_user");
		$password = $this -> session -> userdata("db_pass");
		$current_db = $this -> session -> userdata("db_name");
		$recovery_status = false;

		$this -> load -> dbutil();
		if ($this -> dbutil -> database_exists($current_db)) {

			$link = @mysql_connect($hostname, $username, $password);
			$sql = "SHOW TABLES FROM $current_db";
			$result = @mysql_query($sql, $link);
			$count = mysql_num_rows($result);
			if ($count==0) {
				$mysql_home = realpath($_SERVER['MYSQL_HOME']) . "\mysql";
				$mysql_bin = str_replace("\\", "\\\\", $mysql_home);
				$mysql_con = $mysql_bin . ' -u ' . $username . ' -p' . $password . ' -h ' . $hostname . ' ' . $current_db . ' < ' . $file_path;
				exec($mysql_con);
				$recovery_status = true;
				
				$db_config_file =  str_replace('\tools', '', FCPATH).'application/config/db_conf.php';

				if(file_exists($db_config_file)){
					$file = fopen($db_config_file,"w");
					fwrite($file,"". "\r\n");
					fwrite($file,"<?php ". "\r\n");
					fwrite($file,"\$db['default']['hostname'] = '$hostname';". "\r\n");
					fwrite($file,"\$db['default']['username'] = '$username';". "\r\n");
					fwrite($file,"\$db['default']['password'] = '$password';". "\r\n");
					fwrite($file,"\$db['default']['database'] = '$current_db';". "\r\n");
					fwrite($file,"\$db['default']['port'] = $port;". "\r\n");
					fclose($file);
				}
			}
		}
		$this->delete_file($file_path);
		echo $recovery_status;
	}
	public function delete_file($file_path) {
		if(unlink($file_path)) {
			return true;
		}
		else {
			return false;
		}
	}

	public function uncompress_zip($file_path) {
		$this -> load -> library('unzip');
		$destination_path = realpath(".zip","",$file_path);
		$this -> unzip -> allow(array('sql'));

		if ($this -> unzip -> extract($file_path, substr($destination_path, 0,-4))){
			return true;
		}else{
			return false;
		}
	}

	public function template($data) {
		$data['show_menu'] = 0;
		$data['show_sidemenu'] = 0;
		$this -> load -> module('template');
		$this -> template -> index($data);
	}

}