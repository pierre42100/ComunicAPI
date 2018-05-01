<?php
/**
 * Files functions
 *
 * @author Pierre HUBERT
 */

/**
 * Prepare a file creation
 *
 * @param int $userID The ID of the user owning the file
 * @param string $componentName The name of the target component
 * @return string the file path (based on user_data folder)
 */
function prepareFileCreation(int $userID, string $componentName) : string {

	//Get user data folder name
	$user_data_folder = path_user_data("", true);

	//Determine subfolder name (if any)
	if($userID != 0)
		$subfolder = $componentName."/".$userID."/";
	else
		$subfolder = $componentName."/";

	//Check if folders exists or not
	if(!file_exists($user_data_folder.$subfolder)){
		//Create folders recursively
		mkdir($user_data_folder.$subfolder, 0777, true);

		//Create security file (empty index.html file)
		file_put_contents($user_data_folder.$subfolder."index.html", "");
	}

	//Return folder
	return $subfolder;
}

/**
 * Generate a file name for a new file
 *
 * @param string $directory The target directory
 * @param string $extension The file extension
 * @return string The generated file name
 */
function generateNewFileName(string $directory, string $extension) : string {

	//First, check if folder exists
	if(!file_exists($directory))
		return false;
	
	//Generate a random filename
	do {
		//Generate a new filename
		$fileName = random_str(25).".".$extension;	
	}
	while(file_exists($directory.$fileName));

	//Return the generated filename
	return $fileName;
}

/**
 * Reduces an image size
 *
 * @param string $fileName The name of the image to reduce / "string" to give an image string
 * @param string $targetFile The target of the reduced image
 * @param int $maxWidth The maximal width of the image
 * @param int $maxHeight The maximal height of the image
 * @param string $outFormat The output format
 * @param string $img_string Optionnal, the image string if it is a string
 * @return bool True for a success / False for a failure
 */
function reduce_image(string $fileName, string $targetFile, int $maxWidth, int $maxHeight, string $outFormat = "image/png", string $img_string="") : bool{
	
	//Check if we have to reduce physical image or an image contained in a variable
	if($fileName != "string"){

		//Check if image exists or not
		if(!file_exists($fileName))
			return false;
		
		//Try to get image size
		if(!$imageInfos = getimagesize($fileName))
			return false; //File doesn't seems to be an image

	}
	else {
		//Get informations about the image in the string
		if(!$imageInfos = getimagesizefromstring($img_string))
			return false; //Couldn't create image
	}

	//Extract image width and height
	$width = $imageInfos[0];
	$height = $imageInfos[1];

	//Check image size
	if($width == 0 || $height == 0)
		return false; //Can't process such image

	//Try to open image
	if($fileName === "string")
		$src = imagecreatefromstring($img_string);
	elseif($imageInfos['mime'] === "image/png")
		$src = imagecreatefrompng($fileName);
	elseif($imageInfos['mime'] === "image/jpeg")
		$src = imagecreatefromjpeg($fileName);
	elseif($imageInfos['mime'] === "image/gif")
		$src = imagecreatefromgif($fileName);
	//elseif($imageInfos['mime'] === "image/x-ms-bmp")
	//	$src = imagecreatefrombmp($fileName);
	else
		return false; //Unrecognized image type
	
	//Check if image size can be kept as is
	if($width <= $maxWidth AND $height <= $maxHeight){
		//We keep the same dimensions
		$newWidth = $width;
		$newHeight = $height;
	}
	elseif($width > $maxWidth){
		$newWidth = $maxWidth;
		$newHeight = floor(($height*$maxWidth)/$width);
	}
	else {
		$newHeight = $maxHeight;
		$newWidth = floor(($width*$maxHeight)/$height);
	}

	//Create reduced image
	$dest = imagecreatetruecolor($newWidth, $newHeight);

	//Copy image
	imagecopyresized($dest, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

	//Try to export image
	//PNG
	if($outFormat === "image/png"){
		if(!imagepng($dest, $targetFile, 2))
			return false;
	}

	//JPEG
	elseif($outFormat === "image/jpeg"){
		if(!imagejpeg($dest, $targetFile, 2))
			return false;
	}

	//UNSUPPORTED
	else
		return false; //Unkown export format

	//Success
	return true;
}