<?php

class CoderForLifeTheme extends Theme {
	/**
	 * Execute on theme init to apply these filters to output
	 */
	public function action_init_theme() {
    // Format the comments
    Format::apply('strip_bad_tags', 'comment_content_out');
    Format::apply('geshi', 'comment_content_out');
    Format::apply('linkify', 'comment_content_out');
    Format::apply('autop', 'comment_content_out');
	}

  public function filter_template_user_filters($filters) {
    // show projects when going by tag, searching, or by date
    // the search one is normally empty, and if we keep it empty all things will be displayed
		if ($this->request->display_entries_by_tag || $this->request->display_entries_by_date) { //  || $this->request->display_search
      $filters['content_type'] = Utils::single_array($filters['content_type']);
      $filters['content_type'][] = Post::type('project');
      //$filters['content_type'][] = Post::type('page');
		}
		return $filters;
  }

	/**
	 * Add additional template variables to the template output.
	 *
	 *  You can assign additional output values in the template here, instead of
	 *  having the PHP execute directly in the template.  The advantage is that
	 *  you would easily be able to switch between template types (RawPHP/Smarty)
	 *  without having to port code from one to the other.
	 *
	 *  You could use this area to provide "recent comments" data to the template,
	 *  for instance.
	 *
	 *  Note that the variables added here should possibly *always* be added,
	 *  especially 'user'.
	 *
	 *  Also, this function gets executed *after* regular data is assigned to the
	 *  template.  So the values here, unless checked, will overwrite any existing
	 *  values.
	 */
	public function add_template_vars()
	{
		parent::add_template_vars();

		//Theme Options
		$this->home_tab = 'Home'; //Set to whatever you want your first tab text to be.
		$this->show_author = false; //Display author in posts

		if( !isset( $this->pages ) ) {
			$this->pages = Posts::get( array( 'content_type' => 'page', 'status' => 'published', 'nolimit' => true ) );
		}
		if( !isset( $this->page ) ) {
			$page = Controller::get_var( 'page' );
			$this->page = isset( $page ) ? $page : 1;
		}
		if( !isset( $this->projects ) ) {
			$this->projects = Posts::get( array( 'content_type' => Post::type('project'), 'status' => Post::status('published'), 'nolimit' => 1 ) );
		}
    
		if ( User::identify()->loggedin ) {
			Stack::add( 'template_header_javascript', Site::get_url('scripts') . '/jquery.js', 'jquery' );
		}
	}

	public function my_comment_class( $comment, $post )
	{
		$class = 'class="comment';
		if ( $comment->status == Comment::STATUS_UNAPPROVED ) {
			$class.= ' comment-unapproved';
		}
		// check to see if the comment is by a registered user
		if ( $u = User::get( $comment->email ) ) {
			$class.= ' byuser comment-author-' . Utils::slugify( $u->displayname );
		}
		if( $comment->email == $post->author->email ) {
			$class.= ' bypostauthor';
		}

		$class.= '"';
		return $class;
	}

/**
 * If comments are enabled, or there are comments on the post already, output a link to the comments.
 *
 */
	public function comments_link( $post )
	{
		if ( !$post->info->comments_disabled || $post->comments->approved->count > 0 ) {
			$comment_count = $post->comments->approved->count;
			echo "<span class=\"commentslink\"><a href=\"{$post->permalink}#comments\" title=\"" . _t('Comments on this post') . "\">{$comment_count} " . _n( 'Comment', 'Comments', $comment_count ) . "</a></span>";
		}
	}

  public function action_form_comment($form)
  {
    $required = (Options::get('comments_require_id') == 1) ? '*' : '';
    $this->add_template('formcontrol_text', dirname(__FILE__).'/formcontrol_text.php', true);
    $this->add_template('formcontrol_textarea', dirname(__FILE__).'/formcontrol_textarea.php', true);
  	$form->cf_commenter->caption = "Name$required";
  	$form->cf_email->caption = "Mail$required";
    $form->cf_email->label_title = '(not published)';
  	$form->cf_url->caption = 'Website';
  }
}

?>
