<?php
/**
 * Restserver controller
 * 
 * Posts controller
 * 
 * @author Pierre HUBERT
 */

class postsController {

	/**
	 * Visibility levels for the API
	 */
	const VISIBILITY_LEVELS_API = array(
		Posts::VISIBILITY_PUBLIC => "public",
		Posts::VISIBILITY_FRIENDS => "friends",
		Posts::VISIBILITY_USER => "private"
	);

	/**
	 * Access levels for the POST for the api
	 */
	const ACCESS_LEVEL_API = array(
		Posts::NO_ACCESS => "no-access",
		Posts::BASIC_ACCESS => "basic",
		Posts::INTERMEDIATE_ACCESS => "intermediate",
		Posts::FULL_ACCESS => "full"
	);

	/**
	 * Get user posts
	 * 
	 * @url POST /posts/get_user
	 */
	public function getUserPosts(){

		//Get user ID
		$userID = getPostUserID("userID");

		//Check if user is allowed to access informations or not
		if(!CS::get()->components->user->userAllowed(userID, $userID))
			Rest_fatal_error(401, "You are not allowed to access this user posts !");

		//Check if there is a startpoint for the posts
		if(isset($_POST['startFrom'])){
			$startFrom = toInt($_POST['startFrom']);
		}
		else
			$startFrom = 0; //No start point

		//Get the post of the user
		$posts = CS::get()->components->posts->getUserPosts(userID, $userID, $startFrom);

		//Return parsed list of posts
		return $this->parsePostsList($posts);
	}

	/**
	 * Get the latest posts for the user
	 * 
	 * @url POST /posts/get_latest
	 */
	public function get_latest_posts(){

		user_login_required();

		//Check if there is a startpoint for the posts
		if(isset($_POST['startFrom'])){
			$startFrom = toInt($_POST['startFrom']);
		}
		else
			$startFrom = 0; //No start point

		//Get the post of the user
		$posts = CS::get()->components->posts->get_latest(userID, $startFrom, 10);

		//Return parsed list of posts
		return $this->parsePostsList($posts);

	}


	/**
	 * Get informations about a single post
	 * 
	 * @url POST /posts/get_single
	 */
	public function get_single_post(){

		//Get the post ID
		$postID = getPostPostIDWithAccess("postID");

		//Get informations about the post
		$postInfos = components()->posts->get_single($postID, false);

		//Check for errors
		if(count($postInfos) == 0)
			Rest_fatal_error(500, "Couldn't retrieve post informations !");

		//Check if we can get the comments of the post
		if(components()->user->allowComments($postInfos['user_page_id']))
			$postInfos['comments'] = components()->comments->get($postInfos["ID"]);

		//Parse post informations
		$postInfos = $this->parsePostForAPI($postInfos);

		//Return informations about the post
		return $postInfos;

	}

	/**
	 * Create a post
	 * 
	 * @url POST /posts/create
	 */
	public function createPost(){

		user_login_required(); //Need login

		//Get the kind of page
		if(!isset($_POST['kind-page']) || !isset($_POST['kind-id']))
			Rest_fatal_error(400, "Please specify the kind of target page and its ID !");
		
		//Make the kind of page match with one of those locally stored
		switch($_POST['kind-page']){
			
			//In case of user
			case "user":

				//Get the values
				$kind_page = Posts::PAGE_KIND_USER;
				$kind_page_id = getPostUserID('kind-id');

				//Check if user is allowed to create post on user page
				if(!CS::get()->components->user->canCreatePosts(userID, $kind_page_id))
					Rest_fatal_error(401, "You are not allowed to create post on this page !");

				break;
			
			//Unsupported kind of page
			default:
				Rest_fatal_error(500, "Unsupported kind of page !");
		}

		//Get the kind of post
		if(!isset($_POST['kind']))
			Rest_fatal_error(400, "Please specify the kind of post !");
		$kind = $_POST['kind'];

		//Get the content of the post
		$content = getPostContent("content");

		//Get the visibility of the post
		$visibility = $this->getPostVisibilityLevel("visibility");

		//Act differently depending of the post content
		//For text post
		if($kind === Posts::POST_KIND_TEXT){

			//The post content must be valid
			if(!check_string_before_insert($content))
				Rest_fatal_error(400, "Specified post content invalid !");

		}

		//For image posts
		else if($kind === Posts::POST_KIND_IMAGE){

			//Check if it is a valid file
			if(!check_post_file("image"))
				Rest_fatal_error(400, "An error occured while receiving image !");

			//Save post image
			$file_path = save_post_image("image", userID, "imgpost", 2000, 2000);

			//Save image information
			$file_sys_path = path_user_data($file_path, true);
			$file_type = mime_content_type($file_sys_path);
			$file_size = filesize($file_sys_path);
		}

		//For YouTube posts
		else if($kind === Posts::POST_KIND_YOUTUBE){

			//Check if Youtube ID was specified
			if(!isset($_POST['youtube_id']))
				Rest_fatal_error(400, "Please specify the Youtube video ID in your request !");
			$youtube_id = $_POST['youtube_id'];

			//Check the video ID
			if(!check_youtube_id($youtube_id))
				Rest_fatal_error(400, "Specified YouTube video ID is invalid !");
			
			//Save video informations
			$file_path = $youtube_id;
		}


		//For personnal movies posts
		else if($kind === Posts::POST_KIND_MOVIE){

			//Get movie ID
			$movieID = getPostMovieID("movieID");

			//Check if the current user is the owner the movie or not
			if(userID != CS::get()->components->movies->get_owner($movieID))
				Rest_fatal_error(400, "You are not allowed to use this movie in your posts !");
			
			//Save movie informations
			$video_id = $movieID;
		}

		//For weblinks
		else if($kind === Posts::POST_KIND_WEBLINK){

			//Check if we have a valid url
			if(!check_post_url("url"))
				Rest_fatal_error(400, "Invalid URL specified with request !");
			$url = $_POST['url'];

			//Get informations about the webpage
			$page_infos = URLAnalyzer::analyze($url, 15);

			//Save URL informations
			$link_url = $url;
			$link_title = isset($page_infos["title"]) ? $page_infos["title"] : null;
			$link_description = isset($page_infos["description"]) ? $page_infos["description"] : null;
			$link_image = isset($page_infos["image"]) ? $page_infos["image"] : null;
		}

		//For PDFs
		else if($kind === Posts::POST_KIND_PDF){

			//Check if it is a valid file
			if(!check_post_file("pdf"))
				Rest_fatal_error(400, "An error occured while receiving pdf !");

			//Check file type
			if($_FILES['pdf']['type'] != "application/pdf")
				Rest_fatal_error(400, "The file sent is not a PDF !");
			
			//Generate target file name
			$target_userdata_folder = prepareFileCreation(userID, "post_pdf");
			$target_file_path = $target_userdata_folder.generateNewFileName(path_user_data($target_userdata_folder, true), "pdf");
			$target_file_sys_path = path_user_data($target_file_path, true);

			//Try to move the file to its final location
			if(!move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file_sys_path))
				Rest_fatal_error(500, "Could save the PDF !");

			//Save pdf information
			$file_type = "application/pdf";
			$file_size = filesize($target_file_sys_path);
			$file_path = $target_file_path;
		}

		//For countdown timer
		else if($kind === Posts::POST_KIND_COUNTDOWN){

			//Get end timestamp
			$time_end = getPostTimeStamp("time-end");

		}

		//For survey
		else if($kind === Posts::POST_KIND_SURVEY){

			//Process the question
			if(!isset($_POST['question']))
				Rest_fatal_error(400, "Please specify the question of the survey !");
			$question = $_POST['question'];

			//Check the length of the question
			if(strlen($question) < 5)
				Rest_fatal_error(400, "Please specify a valid question for the survey !");

			//Process the answers
			if(!isset($_POST['answers']))
				Rest_fatal_error(400, "Please specify the ansers of the survey !");
			$str_answers = $_POST["answers"];
			
			//Process the ansers
			$answers = explode("<>", $str_answers);

			//Remove empty questions and make other secure to insert
			foreach($answers as $num=>$val){
				if($val == "" || $val == " ")
					unset($answers[$num]);
				else
					$answers[$num] = removeHTMLnodes($val);
			}

			//Check the minimum number of question is valid
			if(count($answers) < 2)
				Rest_fatal_error(400, "Please specify at least two valid answers for the survey !");

			//Save informations about the survey
			$survey_question = removeHTMLnodes($question);
			$survey_answers = $answers;
		}

		//The content type is not supported
		else {
			Rest_fatal_error(500, "This kind of post is not supported !");
		}

		//Create the post
		$postID = CS::get()->components->posts->create(
			
			//Informations about the kind of page
			$kind_page,
			$kind_page_id,

			//Generic informations about the post
			userID,
			$content,
			$visibility,
			$kind,

			//Specific informations about the post
			//Posts with files
			isset($file_size) ? $file_size : 0,
			isset($file_type) ? $file_type : null,
			isset($file_path) ? $file_path : null,

			//For video post
			isset($video_id) ? $video_id : 0,

			//For countdown post
			isset($time_end) ? $time_end : 0,

			//For weblink posts
			isset($link_url) ? $link_url : null,
			isset($link_title) ? $link_title : null,
			isset($link_description) ? $link_description : null,
			isset($link_image) ? $link_image : null,

			//For survey posts
			isset($survey_question) ? $survey_question : null,
			isset($survey_answers) ? $survey_answers : array()
		);

		//Check for errors
		if($postID < 0)
			Rest_fatal_error(400, "Couldn't create post !");

		//Create a notification
		$notification = new Notification();
		$notification->set_from_user_id(userID);
		$notification->set_on_elem_id($postID);
		$notification->set_on_elem_type(Notification::POST);
		$notification->set_type(Notification::ELEM_CREATED);
		components()->notifications->push($notification);

		//Success
		return array(
			"success" => "The post has been created !",
			"postID" => $postID
		);

	}

	/**
	 * Change the visibility level of a post
	 * 
	 * @url POST /posts/set_visibility_level
	 */
	public function set_visibility_level(){

		user_login_required();
		
		//Get the post ID
		$postID = $this->getFullAccessPostID("postID");
		
		//Get the visibility level
		$new_visibility = $this->getPostVisibilityLevel("new_level");

		//Try to update visibility level
		if(!CS::get()->components->posts->update_level($postID, $new_visibility))
			Rest_fatal_error(500, "Couldn't update visibility level !");

		//Success
		return array("success" => "The visibility level has been updated !");
	}

	/**
	 * Update the content of a post
	 * 
	 * @url POST /posts/update_content
	 */
	public function update_content(){

		user_login_required();
		
		//Get the post ID
		$postID = $this->getFullAccessPostID("postID");

		//Get the post content
		$new_content = getPostContent("new_content");

		//Try to update post content
		if(!components()->posts->update_content($postID, $new_content))
			Rest_fatal_error(500, "An error occured while trying to update post content !");

		//Success
		return array("success" => "The post content has been updated !");
	}

	/**
	 * Delete post
	 * 
	 * @url POST /posts/delete
	 */
	public function delete_post(){

		user_login_required();

		//Get the post ID
		$postID = getPostPostID("postID");

		//Get the user access over the post
		$user_access = CS::get()->components->posts->access_level($postID, userID);

		//Check if the user is allowed to delete the post or not
		if($user_access != Posts::FULL_ACCESS && $user_access != Posts::INTERMEDIATE_ACCESS)
			Rest_fatal_error(401, "You are not allowed to delete this post !");

		//Delete the post
		if(!CS::get()->components->posts->delete($postID))
			Rest_fatal_error(500, "Couldn't delete post!");

		/*//Delete related notifications
		$notification = new Notification();
		$notification->set_on_elem_type(Notification::POST);
		$notification->set_on_elem_id($postID);*/

		//Success
		return array("success" => "The post has been deleted!");
	}


	/**
	 * Get the visibility level specified in a POST request
	 * 
	 * @param string $name The name of the POST parameter
	 * @return int The visibility level
	 */
	private function getPostVisibilityLevel(string $name) : int {

		if(!isset($_POST[$name]))
			Rest_fatal_error(400, "Please specify the visibility of the post !");
		$api_visibility = $_POST[$name];

		//Get the visibility levels of the API
		$post_visibility = array_flip($this::VISIBILITY_LEVELS_API);

		//Check for the existence of the visibility level
		if(!isset($post_visibility[$api_visibility]))
			Rest_fatal_error(400, "Specified visibility level not recognized !");

		//Return it
		return $post_visibility[$api_visibility];

	}

	/**
	 * This function is called to check if the current user has a full access
	 * other a post specified by its ID in a post request
	 * 
	 * @param string $name The name of the POST parameter
	 * @return int The ID of the POST (an error is thrown if the user can't be
	 * authenticated as post owner)
	 */
	private function getFullAccessPostID(string $name) : int {

		user_login_required();
		
		//Get the post ID
		$postID = getPostPostID($name);

		//Check if the user is allowed to change the visibility level of the post
		if(CS::get()->components->posts->access_level($postID, userID) != Posts::FULL_ACCESS)
			Rest_fatal_error(401, "You do not the full control of this post !");
		
		//Return post id
		return $postID;
	}

	/**
	 * Process a list of post for the API
	 * 
	 * @param array $list The list of posts to process
	 * @return array The parsed list of posts for API
	 */
	private function parsePostsList(array $list) : array {

		//Process the list of posts
		foreach($list as $num => $infos){

			//Parse post informations
			$list[$num] = $this->parsePostForAPI($infos);

		}

		return $list;
	}

	/**
	 * Parse a post to make it ready to be displayed on the API
	 * 
	 * @param array $infos Source informations
	 * @return array Post informations ready to be returned
	 */
	private function parsePostForAPI(array $infos) : array {

		//Get access level to the post
		$access_level = CS::get()->components->posts->access_level_with_infos($infos, userID);

		//Save level access in the response
		$infos["user_access"] = $this::ACCESS_LEVEL_API[$access_level];

		//Update visibility level
		$infos['visibility_level'] = $this::VISIBILITY_LEVELS_API[$infos['visibility_level']];

		//Return the post ready to be shown
		return $infos;
	}
}