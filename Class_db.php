<?php 
	/*
	 *	author:JdesHZ
	 *	version:1.2.0
	 * 	date:2018-03-08
	 * 	如有bug,请联系
	 */
	
	/*
	 *	设置文件编码
	 */
	
	header("Content-type: text/html; charset=utf-8");

	/*
	 * DataBase类
	 * 使用时，必须做如下定义
	 * $link=DataBase::dblink($password,$dbname);
	 * $link保留字,请勿作他用;
	 * 如果服务器,端口,用户名称为自定义值,请参照下面的API文档
	 * 新建一个PHP文件
	 * 头部引入:include 'url';(url为该文件的路径)
	 * 函数调用方法:
	 * DataBase::functionName;
	 * 例如
	 * DataBase::setTimezone();
	 */

	class DataBase{
		/*
		 * 设置文件默认时区,如果服务器没有设置默认时区,请在文件开头调用,默认PRC(东八区:北京时间)
		 */
		public static function setTimezone($timecode=PRC){
			date_default_timezone_set($timecode);
		}
		/*
		 * 连接数据库
		 * !@--使用时必须定义一个变量用来传递$link;--@!
		 * 推荐:$link=DataBase::dblink($password,$dbname);
		 * $servername:服务器名称,默认localhost
		 * $username:用户名称,默认root
		 * $password:数据库密码
		 * $dbname:数据库名称
		 * $port:连接端口,默认3306
		 */

		public static function dblink($password,$dbname,$servername=localhost,$username=root,$port=3306){
			@$link =new mysqli($servername,$username,$password,$dbname,$port);
			//测试连接
			if($link->connect_error){
				die("connect_error".$link->connect_error);
			}else{
				return $link;
			}
		}

		/*
		 *	检测字段是否存在，便于处理
		 *
		 */
		
		public static function isname($link,$table,$keyname){
			$sql="SELECT column_name FROM information_schema.columns WHERE TABLE_NAME='$table'";
			$result=mysqli_query($link,$sql);
			$res_t=mysqli_num_rows($result);
			if($res_t>0){
				while($row=mysqli_fetch_assoc($result)){
					$list[]=$row;
				}
				foreach ($list as $key => $value) {
					foreach ($value as $key => $val) {
						if(stristr($val,$keyname)){
							return true;
						}else{
							continue;
						}
					}
				}
			}
		}

		/*
		 *	获取数据表所有字段名(便于通配符查询时,time处理)
		 */

		public static function getName($link,$table){
			$sql="SELECT column_name FROM information_schema.columns WHERE TABLE_NAME='$table'";
			$result=mysqli_query($link,$sql);
			$res_t=mysqli_num_rows($result);
			$arr=[];
			if($res_t>0){
				while($row=mysqli_fetch_assoc($result)){
					$list[]=$row;
				}
				foreach ($list as $key => $value) {
					foreach ($value as $key => $val) {
						array_push($arr,$val);
					}
				}
				$str=implode(',',$arr);
				return $str;
			}
		}

		/*
		 *	处理时间(包括带有time字段的时间戳)
		 * 	返回时间字段数组
		 */
			
		public static function HandleTime($keywords){
			if(stristr($keywords,'time')){
				$arr=explode(',',$keywords);
				$str_arr=[];
				if(stristr($keywords,'time')){
					foreach ($arr as $key => $value) {
						if(stristr($value,'time')){
							array_push($str_arr,$value);
						}
					}
				}
				return $str_arr;
			}else{
				return null;
			}
		}
		/*
		 *	查数据
		 *	$keywords:查询字段,多项请用,隔开;
		 *	$keywords="a,b";
		 *	$condition:查询条件,输入格式数组
		 *	$condition=[
		 *		$key=>$value,
		 *	];
		 *	$key:某一字段名
		 *	$value:值
		 *	表示查询a,b字段,并且在$key=$value的条件下;
		 * 	默认根据ID 降序排列
		 */
		public static function SELECT($link,$table,$keywords,$limit=NULL,$condition=null,$by=ID,$order=DESC){
			if($condition!=null){
				$arr=[];
				foreach ($condition as $key => $value) {
					$value=DataBase::Tostr($value);
					$str=$key.' ="'.$value.'"';
					array_push($arr,$str);
				}
				$str1=implode(' AND ',$arr);
				$keywords=$keywords;
				if($limit==null){
					$sql = "SELECT $keywords FROM $table WHERE $str1 ORDER BY $by $order";
				}else{
					$sql = "SELECT $keywords FROM $table WHERE $str1 ORDER BY $by $order LIMIT $limit";
				}
			}else{
				if($limit==null){
				$sql = "SELECT $keywords FROM $table ORDER BY $by $order";
				}else{
					$sql = "SELECT $keywords FROM $table ORDER BY $by $order LIMIT $limit";
				}
			}	
			if($keywords=='*'){
				$keywords=DataBase::getName($link,$table);
				$str_arr=DataBase::HandleTime($keywords);
			}else{
				$str_arr=DataBase::HandleTime($keywords);
			}
			$result = mysqli_query($link,$sql);
			$res_t=mysqli_num_rows($result);
			if($res_t>0){
				while($row=mysqli_fetch_assoc($result)){
					if(DataBase::isname($link,$table,'time')&&(stristr($keywords,'time')||$keywords=='*')){
						foreach ($str_arr as $key => $value) {
							$row[$value]=date('Y-m-d H:i:s',$row[$value]);
						}
						$list[] = $row;
					}else{
						$list[] = $row;
					}
				}
				$data=json_encode($list);
				return $data;
			} 
		}

		/*
		 *	查询符合某一字段的数据条数,返回json;
		 *	$column_name:查询字段,默认为*
		 *	$condition:查询条件,格式为数组;如下:
		 *	$condition=[
		 *		ID=>7,
		 *	]
		 *	默认导出标识符为: count
		 * 
		 */

		public static function COUNT($link,$table,$condition=null,$default=count,$column_name='*'){
			if($condition==null)
			{
				$sql="SELECT COUNT($column_name) AS count FROM $table";
			}else{
				$arr=[];
				foreach ($condition as $key => $value) {
					$value=DataBase::Tostr($value);
					$str=$key.'='.$value;
					array_push($arr,$str);
				}
				$str=implode(' AND ',$arr);
				$sql="SELECT COUNT($column_name) AS count FROM $table WHERE $str";
			}
			$result = mysqli_query($link,$sql);
			$res_t=mysqli_num_rows($result);
			if($res_t>0){
				while ($row=mysqli_fetch_assoc($result)) {
					$list[] = $row;
				}
				$data = json_encode($list);
				return $data;
			}
		}

		/*
		 *	获取某一页数据,跳页
		 * 	$page:页码编号(当前页码)
		 * 	$pagesum:每一页的数据个数;
		 * 	默认顺序查询,如需倒叙查询,请自行赋值$order=DESC;
		 */

		public static function getpage($link,$page,$page_num,$table,$keywords='*',$by=ID,$order=ASC){
			$t_page=$page-1;
			$tp_page=$t_page*$page_num;
			$sql="SELECT $keywords FROM $table ORDER BY $by $order LIMIT {$tp_page},{$page_num}";
			if($keywords=='*'){
				$keywords=DataBase::getName($link,$table);
				$str_arr=DataBase::HandleTime($keywords);
			}else{
				$str_arr=DataBase::HandleTime($keywords);
			}
			$result=mysqli_query($link,$sql);
			$res_c=mysqli_num_rows($result);
			if($res_c>0){
				while ($row=mysqli_fetch_assoc($result)) {
					if(DataBase::isname($link,$table,'time')&&(stristr($keywords,'time')||$keywords=='*')){
						foreach ($str_arr as $key => $value) {
							$row[$value]=date('Y-m-d H:i:s',$row[$value]);
						}
						$list[] = $row;
					}else{
						$list[] = $row;
					}	
				}
				$data = json_encode($list);
				return $data;
			}
		}

		/*
		 *	添加数据
		 *	$arr格式(数组): $arr=[
		 *		'key'=>'value',
		 *	];
		 *	@ 另一种数组定义方式也可以,保证输入为数组即可
		 *	key:代表数据表字段名;
		 *	value:序列化表单提交数据键值;
		 * 	如需提交时间(例如创建时间),请直接输入提交时间的字段名称
		 * 	即$timename=字段名(多个用,隔开);
		 * 	例如$timename="creatTime,time,loginTime";
		 *
		 */
		public static function INSERT($link,$table,$arr=null,$timename=null){
			$arr1=[];
			$arr2=[];
			foreach ($arr as $key => $value) {
				array_push($arr1,$key);
				array_push($arr2,$value);
			}
			if($timename!=null){
				$timearr=explode(',',$timename);
				$count=count($timearr);
				$str=implode(',',$timearr);
				$timearr=[];
				for($i=0;$i<$count;$i++){
					array_push($timearr,time());
				}
				$str1='ID,'.$str.','.implode(',', $arr1);
				$str2='0,'.implode(',',$timearr).',"'.implode('","', $arr2).'"';
			}else{
				$str1='ID,'.implode(',', $arr1);
				$str2='0,"'.implode('","', $arr2).'"';
			}
			$sql="INSERT INTO $table ($str1) VALUE($str2)";
			$result=mysqli_query($link,$sql);
			if($result){
				return 'success';
			}else{
				return 'error';
			}
		}

		/*
		 *	更新数据
		 * 	$keyname:更新数据的字段,以数组形式传递;
		 * 	$keyname=[
		 * 		a=>45,
		 * 	];
		 * 	表示将数据表字段为a的字段的值更新为45;
		 * 	$condition:需要更新数据的字段条件,以数组形式传递;
		 * 	$condition=[
		 * 		a=>100,
		 * 	];
		 * 	以上表示将数据库字段名为a的原始值(100)替换为45,多个值或者多个条件请依次往后输入;
		 * 	如果需要更新time;
		 * 	请输入需要更改的time 的字段名,多项请用,隔开
		 * 	$time="createTime,time";
		 */

		public static function UPDATE($link,$table,$keyname=null,$condition=null,$time=null){
			$arr1=[];
			foreach ($condition as $key => $value) {
				$value=DataBase::Tostr($value);
				$str=$key.'='.$value;
				array_push($arr1,$str);
			}
			$arr2=[];
			foreach ($keyname as $key => $value) {
				$str=$key.'="'.$value.'"';
				array_push($arr2,$str);
			}
			if($time==null){
				$str1=implode(',',$arr2);
			}else{
				$arr=explode(',',$time);
				$arr_t=[];
				for($i=0;$i<count($arr);$i++){
					array_push($arr_t,time());
				}
				$arrTime=array_combine($arr,$arr_t);
				$arr3=[];
				foreach ($arrTime as $key => $value) {
				$str=$key.'='.$value;
				array_push($arr3,$str);
				}
				$str1=implode(',',$arr2).','.implode(',',$arr3);
			}
			$str2=implode(' AND ',$arr1);
			$sql="UPDATE $table SET $str1 WHERE $str2";
			$result=mysqli_query($link,$sql);
			if($result){
				return 'success';
			}else{
				return 'error';
			}
		}

		/*
		 *	删除数据
		 *	$condition:删除条件,格式为数组,如下:
		 *	$condition=[
		 *		ID=>5,
		 *	]
		 *	
		 */

		public static function DELETE($link,$table,$condition=null){
			$arr=[];
			foreach ($condition as $key => $value) {
				$value=DataBase::Tostr($value);
				$str=$key.'='.$value;
				array_push($arr,$str);
			}
			$str=implode(' AND ',$arr);
			$sql="DELETE FROM $table WHERE $str";
			$result=mysqli_query($link,$sql);
			if($result){
				return 'success';
			}else{
				return 'error';
			}
		}


		/*
		 *	生成随机长度的字符串,用作密码盐化(或者验证码);
		 *	默认盐化长度12;
		 */
		
		public static function Ranchars($length=12){
			$code = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
			for($i=0;$i<$length;$i++){
				$str .= $code[mt_rand(0, strlen($code)-1)]; 
			}
			return $str;
		}

		/*
		 *	密码操作类---------------------------------
		 */
		

		/*
		 *	创建密码(用于注册)
		 *	默认sha1,加密次数2次,自动盐化,次数请不要改变,过高影响性能
		 *	如需更换加密模式请直接输入:
		 *	$mode='md5',请使用一种方式加密(支持md5/sha1)
		 * 	支持md5/sha1,盐化代码自动生成,
		 * 	$userinfo:提交到数据库的密码(用户名)所在字段,以及创建的密码(用户名)(可事先加密),数据格式为数组;
		 * 	$userinfo=[
		 * 		psd=>e6667ca4dfe6f680531f76d12d1bbcdd,
		 * 		username=>YUTE89,
		 * 	];
		 * 	提交数据字段如需更改请自行更换,建议保留默认值
		 * 	默认不创建用户密码数据创建时间,如需添加,请输入储存字段$timename;
		 * 	
		 */
		
		public static function CreatePsd($link,$table,$userinfo,$timename=null,$mode=sha1){
			$salt=DataBase::Ranchars();
			$userinfo['salt']=$salt;
				if($mode=='md5'){
					$userinfo['psd']=md5(md5($userinfo['psd']).md5($salt));
				}else{
					$userinfo['psd']=sha1(sha1($userinfo['psd']).sha1($salt));
				}
			return DataBase::INSERT($link,$table,$userinfo,$timename);
		}



		/*
		 *	密码验证(用于登录,修改用户名,修改密码)
		 *	登录验证模式应该和密码创建加密模式一致,否则会出错
		 *	推荐不改变登录/创建密码的加密模式
		 *	$userinfo同创建密码,用数组形式传入
		 *	所以数据字段名应该与创建数据字段相同,否则会出错
		 */
		
		public static function verify($link,$table,$userinfo,$mode=sha1){
			$salt= DataBase::SELECT($link,$table,'salt','',[username=>$userinfo['username']]);
			$arr=json_decode($salt,TRUE);
			foreach ($arr as $key => $value) {
				$salt=$value['salt'];
			}
			if ($mode=='md5') {
				$psd=md5(md5($userinfo['psd']).md5($salt));
			}else{
				$psd=sha1(sha1($userinfo['psd']).sha1($salt));
			}
			$password=DataBase::SELECT($link,$table,'psd','',[username=>$userinfo['username']]);
			$arr1=json_decode($password,true);
			foreach ($arr1 as $key => $value) {
				if($psd===$value['psd']){//防止hash漏洞
					return "Verification passed";
				}else{
					return "Verification failed";
				}
			}	
		}

		/*
		 *	创建数据表
		 *	$password:数据库密码;
		 *	$dbname:创建数据表的数据库名称;
		 *	$tablename:创建的表格名称;
		 *	$colunms:创建的字段以及数据格式,提交格式为数组
		 *	$columns=[
		 *		time=>'INT',
		 *		a=>'DOUBLE(6,2)',
		 *		info=>'VARCHAR(10)',
		 *	]
		 */
		public static function CreateSQL($password,$dbname,$tablename=null,$columns=null){
			$servername = "localhost";
			$username = "root";
			$conn = new mysqli($servername, $username, $password, $dbname);
				// 检测连接
			if ($conn->connect_error) {
			die("连接失败: " . $conn->connect_error);
			}
			$arr=[];
			foreach ($columns as $key => $value) {
				$str=$key.' '.$value;
				array_push($arr,$str);
			}
			$str='id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,'.implode(' NOT NULL,',$arr);
			$sql="CREATE TABLE {$tablename} ($str)";
			if ($conn->query($sql) === TRUE) {
			echo "create-success";
			} else {
			echo "创建数据表错误: " . $conn->error;
			}
			$conn->close();
		}

		/*
		 *	待添加功能----------------2018/03/08 18:04
		 */
		

		/*
		 *	处理危险字符
		 *	防止SQL注入
		 *	
		 */

		public static function Tostr($str){
			$str=str_replace(';','%alt',$str);//处理;
			$str=str_replace('-','%blt',$str);//处理-
			$str=str_replace('@','%clt',$str);//处理@
			$str=str_replace('|','%dlt',$str);//处理|
			$str=str_replace('#','%elt',$str);//处理#
			$str=str_replace('$','%flt',$str);//处理$
			$str=str_replace('%','%glt',$str);//处理%
			$str=str_replace('&','%hlt',$str);//处理&
			$str=str_replace('[','%ilt',$str);//处理[
			$str=str_replace(']','%jlt',$str);//处理]
			$str=str_replace('<','%klt',$str);//处理<
			$str=str_replace('>','%llt',$str);//处理>
			$str=str_replace('(','%mlt',$str);//处理(
			$str=str_replace(')','%nlt',$str);//处理)
			$str=str_replace('!','%olt',$str);//处理!
			return $str;
		}


		/*
		 *	待添加功能 2018-03-10 10:23
		 */

	}
	
 ?>
