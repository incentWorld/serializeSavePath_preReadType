<?php

//DB化したい変数以外の読み込む変数より、必ず前に定義しなければならない、つまり保存したい変数はこの部分より前に記述しなければならない
$defaultVariableNamesArray = array_values(array_diff(array_keys(get_defined_vars()), array(
	'_GET'
	,'_POST'
	,'_COOKIE'
	,'_FILES'
	,'_SERVER'
	,'GLOBALS'
	,'_ENV'
	,'_REQUEST'
)));

// print_r(
// $defaultVariableNamesArray
// );

clearstatcache();

// $targetSerializeSaveDir = $_SERVER["DOCUMENT_ROOT"].'/app/dynamic_variable_setting';		//上書きされるので、今は意味が無い
// $targetSerializeSaveDir=null;
// ----------------------------------------------------------------------------------------------------------------
// --- --- --- --- --- 　　　 --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
// ----------------------------------------------------------------------------------------------------------------
// $nothingVariableNameFileDeleteFlag は、$targetSerializeSaveDir のディレクトリに存在しない変数のファイルを削除するかどうか？(true=削除する)
// $targetSerializeSaveDir は、保存するディレクトリを指定します、無い場合にはglobalの $targetSerializeSaveDir を用います。
// $defaultVariableNamesArray は、その配列に含まれない、.serialize_data の拡張子のファイルを削除したりするチェックに用います。
//	$globalPollution_targetSerializeSaveDirFlagは、globalの $targetSerializeSaveDir を上書きします。(true=上書きする デフォルト=true)
function serializeSaveInit($nothingVariableNameFileDeleteFlag, $targetSerializeSaveDir='', $defaultVariableNamesArray,$globalPollution_targetSerializeSaveDirFlag=true){


	// print_r(array_keys(get_defined_vars()));
	// print_r(array_keys($GLOBALS['GLOBALS']));

	if($targetSerializeSaveDir==''){
		global $targetSerializeSaveDir;
	}elseif($globalPollution_targetSerializeSaveDirFlag){		//globalの $targetSerializeSaveDir を上書きします。
		$temp_targetSerializeSaveDir=$targetSerializeSaveDir;
		global $targetSerializeSaveDir;
		$targetSerializeSaveDir=$temp_targetSerializeSaveDir;
		unset($temp_targetSerializeSaveDir);
	}
	$targetSerializeSaveDir=rtrim(rtrim($targetSerializeSaveDir,'/'),'\\');
	if(is_dir($targetSerializeSaveDir)){

		$fileNames = array_diff(scandir($targetSerializeSaveDir), array(
			'.',
			'..'
		));

		foreach ($fileNames as $fileName) {
			(is_dir("$targetSerializeSaveDir/$fileName")) ? delTree("$targetSerializeSaveDir/$fileName") : ((!in_array(preg_replace('/\.serialize_data$/i', '', $fileName), $defaultVariableNamesArray)) ? (($nothingVariableNameFileDeleteFlag && file_exists("$targetSerializeSaveDir/$fileName")) ? unlink("$targetSerializeSaveDir/$fileName") : '') : '');
		}

	}else{
		$octChange=base_convert(755,8,10);		//直観的には、10進であるべき記述を桁でして、それを8進数であると言い切り、それを10進に直す・・・(↓でも、どちらでもいい)
		// $octChange=(int)0755;								//こちらは、直観的でない・・・変数に、0が頭であると、8進数とならない、ちゃんと型でint  して8進を10進にする(↑でも、どちらでもいい)
		recursive_mkdir($targetSerializeSaveDir,$octChange,true);
	}

	if(is_dir($targetSerializeSaveDir)){
		foreach ($defaultVariableNamesArray as $oneDynamicVariableName) {
			if (!file_exists("{$targetSerializeSaveDir}/{$oneDynamicVariableName}.serialize_data")) {
// echo 'test!!!!!!!:<pre>';
// var_dump("{$targetSerializeSaveDir}/{$oneDynamicVariableName}.serialize_data",$GLOBALS[$oneDynamicVariableName]);
				saveSerializeData("{$targetSerializeSaveDir}/{$oneDynamicVariableName}.serialize_data", $GLOBALS[$oneDynamicVariableName]);
			}
		}
	}

}


//*********************************************************************************************************************************************************************************************************
// フォルダの階層化　Linux とWindows の共通関数（Linux とWindows とでは関数の挙動が違う為、共通化）
if(!function_exists('recursive_mkdir')){

	function recursive_mkdir($pathname, $mode, $recursive) {
		if(!$recursive) {
			return mkdir($pathname, $mode);
		}
		if(!is_dir(dirname($pathname))) {	//親のディレクトリが無い場合
			if(recursive_mkdir(dirname($pathname), $mode, $recursive)) {	//回帰的に親ディレクトリを作ろうとする
				return false;
			}
		}

		if(!is_dir($pathname)){		//作ろうとするディレクトリが存在しない場合
			mkdir($pathname, $mode);
		}
	}

}

// ----------------------------------------------------------------------------------------------------------------
if(!function_exists('delTree')){

	// 再帰的にディレクトリの中身を含め削除する
	function delTree($dir){
		if(is_dir($dir)){
			$files = array_diff(scandir($dir), array(
				'.',
				'..'
			));
			foreach ($files as $file) {
				(is_dir("$dir/$file")) ? delTree(rtrim(rtrim($dir,'/'),'\\')."/$file") : unlink("$dir/$file");
			}
			return @rmdir($dir);
		}else{
			return false;
		}
	}

}



// delTree イテレータ版
// foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
// 	$path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
// }
// rmdir($dirPath);



// ----------------------------------------------------------------------------------------------------------------
// --- --- --- --- --- シリアライズの関数 --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
// ----------------------------------------------------------------------------------------------------------------

// イメージのシリアライズデータを保存する(成功すればtrue,失敗したらfalse を返す)
function saveSerializeData($pathAndSerializeDataName, $data){
// 	if ($data || is_array($data)) {
		$rerValue=true;
		$fp = fopen($pathAndSerializeDataName, 'w');
		if($fp===false)return false;
		if(fwrite($fp, serialize($data))===false)$rerValue=false;
		if(fclose($fp)===false)return false;
		return $rerValue;
// 	}
}

// イメージのシリアライズデータを読み込む
function loadSerializeData($pathAndSerializeDataName){

	// ファイルが存在すれば
	if (is_file($pathAndSerializeDataName)) {

		if ($plainSerializeData = file_get_contents($pathAndSerializeDataName)) {
			return unserialize($plainSerializeData);
		} else {
			return NULL;
		}
	}
}

// ----------------------------------------------------------------------------------------------------------------
// --- --- --- --- --- 動的変数保存の関数 --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
// ----------------------------------------------------------------------------------------------------------------

// 指定のシリアライズされた配列の中の指定の部分を変数として取り出して返す
function getVariableData_for_serialize($serializeVariablePath,$targetSerializeSaveDir='',$nullFix=true){
// 	global $targetSerializeSaveDir;
// 	$targetSerializeSaveDir = 'dynamic_variable_setting';
	if($targetSerializeSaveDir==''){
		global $targetSerializeSaveDir;
	}
	$targetSerializeSaveDir=rtrim(rtrim($targetSerializeSaveDir,'/'),'\\');

	$parsePathArray = explode('/', $serializeVariablePath);


	if(! is_array($parsePathArray))return null;
	if(count($parsePathArray) < 1)return null;
	if($parsePathArray[0]==='')return null;
	if($parsePathArray[count($parsePathArray)-1]==='')array_pop($parsePathArray);

	$leadData = loadSerializeData("{$targetSerializeSaveDir}/{$parsePathArray[0]}.serialize_data");

	for ($arrayCount = 1; $arrayCount < count($parsePathArray); $arrayCount ++) {
		if (isset($leadData[$parsePathArray[$arrayCount]])) {
			$leadData = $leadData[$parsePathArray[$arrayCount]];
		}
	}

	//消したりしていると、不思議な内容、array(""=>NULL); を入れるという事象があるのを修正、フラグは第三引数
	if($nullFix && gettype($leadData)==='array'){
		if(count($leadData)===1 && key($leadData)===''){
			$leadData=null;
		}
	}
	return $leadData;
}


// 指定したシリアライズのデータパスに従い繰り返し配列を探索し指定の部分の配列をポインタとして取り出す（自身の配列を子に、指定したパスをキーとして持った親に入れていく）
function &getReferencePointerArray_for_pathArray($ecursivePathArray, &$targetPointArray)
{
	if (! is_array($ecursivePathArray))return false;
	if (0 === count((array) $ecursivePathArray))return $targetPointArray;

	$buildArray = &$targetPointArray;
	for ($inc = 0; $inc < count($ecursivePathArray); $inc ++) {

		$buildArray = &$buildArray[$ecursivePathArray[$inc]];
	}
	return $buildArray;
}


// 特定の配列を指定して追加セーブ（その１）、付け加えたい配列の有無を確認しない
function variable_array_serializeSave_store($serializeVariablePath,$addKeyName,$addValue,$targetSerializeSaveDir=''){
// 	global $targetSerializeSaveDir;
// 	$targetSerializeSaveDir = 'dynamic_variable_setting';

	if($targetSerializeSaveDir==''){
		global $targetSerializeSaveDir;
	}
	$targetSerializeSaveDir=rtrim(rtrim($targetSerializeSaveDir,'/'),'\\');

	// 現在の変数の場所を表すGET 'variable' これは/ 区切りで多次元配列を/ で表しパスの様にしたもの
	$parsePathArray = explode('/', $serializeVariablePath);

	if(! is_array($parsePathArray))return null;
	if(count($parsePathArray) < 1)return null;
	if($parsePathArray[0]==='')return null;
	if($parsePathArray[count($parsePathArray)-1]==='')array_pop($parsePathArray);
	$baseVariableFileName = array_shift($parsePathArray); // a/b/c/d という文字列なら、array('a','b','c','d')となり、 $baseVariableFileName = a, $parsePathArray = array('b','c','d');

	if($addKeyName==''){
		$baseArray = $addValue;
	}else{
		$baseArray = getVariableData_for_serialize($baseVariableFileName,$targetSerializeSaveDir); // 変更ベースとなる(array_shift関数の後で頭がカットされている必要がある事)

		$p_baseInnerArray = &getReferencePointerArray_for_pathArray($parsePathArray, $baseArray);	//ポインターなので、配列の内部を触ると大本に影響できる

		$p_baseInnerArray[$addKeyName] = $addValue;
	}


	return saveSerializeData("$targetSerializeSaveDir/{$baseVariableFileName}.serialize_data", $baseArray);
}


// 指定したシリアライズのデータパスに従い繰り返し配列に入れて指定の階層化された配列を作る（自身の配列を子に、指定したパスをキーとして持った親に入れていく）
function createSirialzeDataPath_recursiveAddToArray($ecursivePathArray, $changeArray)
{
	if (! is_array($ecursivePathArray))
		return false;
		if (0 === count((array) $ecursivePathArray))
			return $changeArray;

			$buildArray = array();
			for ($dec = count($ecursivePathArray) - 1; 0 <= $dec; $dec --) {

				$buildArray = array(
					$ecursivePathArray[$dec] => ($dec == (count($ecursivePathArray) - 1)) ? $changeArray : $buildArray
				);
			}
			return $buildArray;
}


//動的変数をグローバル変数へ強制上書き
function forcedOverwrite_dynamicVariable($target_dynamicVariableNamesArray,$targetSerializeSaveDir=''){

	if($targetSerializeSaveDir==''){
		global $targetSerializeSaveDir;
	}
	$targetSerializeSaveDir=rtrim(rtrim($targetSerializeSaveDir,'/'),'\\');

	foreach ($target_dynamicVariableNamesArray as $oneDynamicVariableName){

		global ${$oneDynamicVariableName};
		${$oneDynamicVariableName}=getVariableData_for_serialize($oneDynamicVariableName,$targetSerializeSaveDir);
	}
}

