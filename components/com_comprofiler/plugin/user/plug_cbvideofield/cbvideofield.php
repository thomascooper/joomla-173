<?php
if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_PLUGINS;
$_PLUGINS->loadPluginGroup( 'user', array( 1 ) );
$_PLUGINS->registerUserFieldTypes( array( 'video' => 'CBfield_video' ) );
$_PLUGINS->registerUserFieldParams();

class CBfield_video extends CBfield_text {

	public function getField( &$field, &$user, $output, $reason, $list_compare_types ) {
		$return				=	null;

		switch ( $output ) {
			case 'html':
			case 'rss':
				$value		=	$user->get( $field->get( 'name' ) );

				if ( $value ) {
					$return	=	$this->getEmbed( $field, $value, ( $reason != 'profile' ) );
				}
				break;
			default:
				$return		=	parent::getField( $field, $user, $output, $reason, $list_compare_types );
				break;
		}

		return $return;
	}

	function validate( &$field, &$user, $columnName, &$value, &$postdata, $reason ) {
		$validated			=	parent::validate( $field, $user, $columnName, $value, $postdata, $reason );

		if ( $validated && ( $value !== '' ) && ( $value !== null ) ) {
			if ( ! $this->getEmbed( $field, $value, ( $reason != 'profile' ) ) ) {
				$this->_setValidationError( $field, $user, $reason, CBTxt::T( 'Provider not supported.' ) );

				$validated	=	false;
			}
		}

		return $validated;
	}

	public function getEmbed( $field, $value, $thumb = false ) {
		$urlDomain					=	preg_replace( '/^(?:(?:\w+\.)*)?(\w+)\..+$/', '\1', parse_url( $value, PHP_URL_HOST ) );
		$embed						=	null;

		if ( $urlDomain ) {
			$currentScheme			=	( ( isset( $_SERVER['HTTPS'] ) && ( ! empty( $_SERVER['HTTPS'] ) ) && ( $_SERVER['HTTPS'] != 'off' ) ) ? 'https' : 'http' );
			$urlScheme				=	parse_url( $value, PHP_URL_SCHEME );

			if ( ! $urlScheme ) {
				$urlScheme			=	$currentScheme;
			}

			$canHttps				=	false;

			if ( ( $currentScheme == 'https' ) && ( $urlScheme != $currentScheme ) ) {
				$urlScheme			=	'https';
				$forcedHttps		=	true;
			} else {
				$forcedHttps		=	false;
			}

			if ( $thumb ) {
				$width				=	(int) $field->params->get( 'video_thumbwidth', 150 );
			} else {
				$width				=	(int) $field->params->get( 'video_width', 350 );
			}

			if ( $thumb ) {
				$height				=	(int) $field->params->get( 'video_thumbheight', 150 );
			} else {
				$height				=	(int) $field->params->get( 'video_height', 250 );
			}

			if ( ( ! $width ) || ( $width <= 0 ) ) {
				$width				=	350;
			}

			if ( ( ! $height ) || ( $height <= 0 ) ) {
				$height				=	250;
			}

			$exclude				=	explode( '|*|', $field->params->get( 'video_exclude', 'unknown' ) );
			$matches				=	null;

			switch ( $urlDomain ) {
				case 'youtube':
				case 'youtu':
					if ( ( ! in_array( 'youtube', $exclude ) ) && preg_match( '%^.*(?:v=|v/|/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.youtube.com/v/' . $matches[1] . '&amp;fs=1&amp;rel=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://www.youtube.com/v/' . $matches[1] . '&amp;fs=1&amp;rel=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'veoh':
					if ( ( ! in_array( 'veoh', $exclude ) ) && preg_match( '#^.*(?:watch%3D|watch/)([\w-]+).*#', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.3.1004&amp;permalinkId=' . $matches[1] . '&amp;player=videodetailsembedded&amp;videoAutoPlay=0&amp;id=anonymous" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://www.veoh.com/static/swf/webplayer/WebPlayer.swf?version=AFrontend.5.5.3.1004&amp;permalinkId=' . $matches[1] . '&amp;player=videodetailsembedded&amp;videoAutoPlay=0&amp;id=anonymous" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'dailymotion':
					if ( ( ! in_array( 'dailymotion', $exclude ) ) && preg_match( '%^.*video/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.dailymotion.com/swf/video/' . $matches[1] . '?additionalInfos=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.dailymotion.com/swf/video/' . $matches[1] . '?additionalInfos=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'yahoo':
					if ( ( ! in_array( 'yahoo', $exclude ) ) && preg_match( '%^.*watch/([\w-]+)/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="id=' . $matches[2] . 'vid=' . $matches[1] . '&amp;embed=1" />'
									.		'<embed src="http://d.yimg.com/static.video.yahoo.com/yep/YV_YEP.swf?ver=2.2.46" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="id=' . $matches[2] . 'vid=' . $matches[1] . '&amp;embed=1"></embed>'
									.	'</object>';
					}
					break;
				case 'vimeo':
					if ( ( ! in_array( 'vimeo', $exclude ) ) && preg_match( '%^.*(?:clip_id=|/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=' . $matches[1] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=0&amp;loop=0" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://vimeo.com/moogaloop.swf?clip_id=' . $matches[1] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=1&amp;fullscreen=1&amp;autoplay=0&amp;loop=0" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'break':
					if ( ( ! in_array( 'break', $exclude ) ) && preg_match( '%^.*/([\w-=]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'
									.		'<param name="movie" value="http://embed.break.com/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://embed.break.com/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'myspace':
					if ( ( ! in_array( 'myspace', $exclude ) ) && preg_match( '/^.*(?:videoid=|m=)([\w-]+).*/', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://mediaservices.myspace.com/services/media/embed.aspx/m=' . $matches[1] . ',t=1,mt=video" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://mediaservices.myspace.com/services/media/embed.aspx/m=' . $matches[1] . ',t=1,mt=video" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'blip':
					if ( ( ! in_array( 'blip', $exclude ) ) && preg_match( '%^.*play/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://blip.tv/play/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . $urlScheme . '://blip.tv/play/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'viddler':
					if ( ( ! in_array( 'viddler', $exclude ) ) && preg_match( '%^.*player/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.viddler.com/player/' . $matches[1] . '/" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="fake=1" />'
									.		'<embed src="' . $urlScheme . '://www.viddler.com/player/' . $matches[1] . '/" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="fake=1"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'flickr':
					if ( ( ! in_array( 'flickr', $exclude ) ) && preg_match( '%^.*(?:photo_id=|photos/[\w-]+/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.flickr.com/apps/video/stewart.swf?photo_id=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.flickr.com/apps/video/stewart.swf?photo_id=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'metacafe':
					if ( ( ! in_array( 'metacafe', $exclude ) ) && preg_match( '%^.*(?:watch/|fplayer/)([\w-]+)/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.metacafe.com/fplayer/' . $matches[1] . '/' . $matches[2] . '.swf" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="playerVars=showStats=yes|autoPlay=no" />'
									.		'<embed src="http://www.metacafe.com/fplayer/' . $matches[1] . '/' . $matches[2] . '.swf" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="playerVars=showStats=yes|autoPlay=no"></embed>'
									.	'</object>';
					}
					break;
				case 'liveleak':
					if ( ( ! in_array( 'liveleak', $exclude ) ) && preg_match( '%^.*(?:i=|e/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.liveleak.com/e/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://www.liveleak.com/e/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'gametrailers':
					if ( ( ! in_array( 'gametrailers', $exclude ) ) && preg_match( '%^.*(?:mid=|video/[\w-]+/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.gametrailers.com/remote_wrap.php?mid=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="quality" value="high" />'
									.		'<embed src="http://www.gametrailers.com/remote_wrap.php?mid=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" quality="high"></embed>'
									.	'</object>';
					}
					break;
				case 'hulu':
					if ( ( ! in_array( 'hulu', $exclude ) ) && preg_match( '%^.*embed/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.hulu.com/embed/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.hulu.com/embed/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'cnn':
					if ( ( ! in_array( 'cnn', $exclude ) ) && preg_match( '%^.*(?:video/|videoId=)([\w-/.]+)%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://i.cdn.turner.com/cnn/.element/apps/cvp/3.0/swf/cnn_416x234_embed.swf?context=embed&amp;videoId=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="http://i.cdn.turner.com/cnn/.element/apps/cvp/3.0/swf/cnn_416x234_embed.swf?context=embed&amp;videoId=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
					}
					break;
				case 'megavideo':
					if ( ( ! in_array( 'megavideo', $exclude ) ) && preg_match( '%^.*v/([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.megavideo.com/v/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.megavideo.com/v/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'blogtv':
					if ( ( ! in_array( 'blogtv', $exclude ) ) && preg_match( '%^.*(?:/|vb/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://www.blogtv.com/vb/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://www.blogtv.com/vb/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'expotv':
					if ( ( ! in_array( 'expotv', $exclude ) ) && preg_match( '%^.*(?:/|embed/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . $urlScheme . '://www.expotv.com/video/embed/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="wmode" value="transparent" />'
									.		'<embed src="' . $urlScheme . '://www.expotv.com/video/embed/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" wmode="transparent"></embed>'
									.	'</object>';
						$canHttps	=	true;
					}
					break;
				case 'g4tv':
					if ( ( ! in_array( 'g4tv', $exclude ) ) && preg_match( '%^.*(?:videos/|lv3/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000">'
									.		'<param name="movie" value="http://g4tv.com/lv3/' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://g4tv.com/lv3/' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'revver':
					if ( ( ! in_array( 'revver', $exclude ) ) && preg_match( '%^.*(?:video/|mediaId=)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://flash.revver.com/player/1.0/player.swf?mediaId=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="allowFullScreen=true" />'
									.		'<embed src="http://flash.revver.com/player/1.0/player.swf?mediaId=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="allowFullScreen=true"></embed>'
									.	'</object>';
					}
					break;
				case 'spike':
					if ( ( ! in_array( 'spike', $exclude ) ) && preg_match( '%^.*(?:spike\.com:|video/[\w-]+/)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:ifilm:video:spike.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="quality" value="high" />'
									.		'<param name="flashvars" value="autoPlay=false" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:ifilm:video:spike.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" quality="high" flashvars="autoPlay=false"></embed>'
									.	'</object>';
					}
					break;
				case 'mtv':
					if ( ( ! in_array( 'mtv', $exclude ) ) && preg_match( '%^.*(?:mtv\.com:|videos/[\w-]+/)([\w-]+).*%', $value, $matches ) ) {
						if ( preg_match( '%^.*(?:videolist:|/playlist).*%', $value ) ) {
							$embed	=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:uma:videolist:mtv.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="configParams=id=' . $matches[1] . '&amp;uri=mgid:uma:videolist:mtv.com:' . $matches[1] . '" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:uma:videolist:mtv.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="configParams=id=' . $matches[1] . '&amp;uri=mgid:uma:videolist:mtv.com:' . $matches[1] . '"></embed>'
									.	'</object>';
						} else {
							$embed	=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://media.mtvnservices.com/mgid:uma:video:mtv.com:' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<param name="flashvars" value="configParams=vid=' . $matches[1] . '&amp;uri=mgid:uma:video:mtv.com:' . $matches[1] . '" />'
									.		'<embed src="http://media.mtvnservices.com/mgid:uma:video:mtv.com:' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '" flashvars="configParams=vid=' . $matches[1] . '&amp;uri=mgid:uma:video:mtv.com:' . $matches[1] . '"></embed>'
									.	'</object>';
						}
					}
					break;
				case 'stupidvideos':
					if ( ( ! in_array( 'stupidvideos', $exclude ) ) && preg_match( '/^.*(?:#|i=)([\w-]+).*/', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://images.stupidvideos.com/2.0.2/swf/video.swf?sa=1&amp;sk=7&amp;si=2&amp;i=' . $matches[1] . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://images.stupidvideos.com/2.0.2/swf/video.swf?sa=1&amp;sk=7&amp;si=2&amp;i=' . $matches[1] . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				case 'youku':
					if ( ( ! in_array( 'youku', $exclude ) ) && preg_match( '%^.*(?:sid/|id_)([\w-]+).*%', $value, $matches ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="http://player.youku.com/player.php/sid/' . $matches[1] . '/v.swf" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="http://player.youku.com/player.php/sid/' . $matches[1] . '/v.swf" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
				default:
					if ( ! in_array( 'unknown', $exclude ) ) {
						$embed		=	'<object width="' . $width . '" height="' . $height . '">'
									.		'<param name="movie" value="' . htmlspecialchars( $value ) . '" />'
									.		'<param name="allowfullscreen" value="true" />'
									.		'<param name="allowscriptaccess" value="always" />'
									.		'<embed src="' . htmlspecialchars( $value ) . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="' . $width . '" height="' . $height . '"></embed>'
									.	'</object>';
					}
					break;
			}

			if ( $embed && $forcedHttps && ( ! $canHttps ) ) {
				$embed				=	'<a href="' . htmlspecialchars( $value ) . '" target="_blank">' . CBTxt::T( 'You are viewing this page securely, but this video does not support secure URLs. Please click here to view this video.' ) . '</a>';
			}
		}

		return $embed;
	}

	public function loadProviders( $name, $value, $control_name ) {
		$providers		=	array();
		$providers[]	=	moscomprofilerHTML::makeOption( 'unknown', CBTxt::T( 'Unknown' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'youtube', CBTxt::T( 'YouTube' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'veoh', CBTxt::T( 'Veoh' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'dailymotion', CBTxt::T( 'Dailymotion' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'yahoo', CBTxt::T( 'Yahoo' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'vimeo', CBTxt::T( 'Vimeo' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'break', CBTxt::T( 'Break' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'myspace', CBTxt::T( 'MySpace' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'blip', CBTxt::T( 'Blip' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'viddler', CBTxt::T( 'Viddler' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'flickr', CBTxt::T( 'Flickr' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'metacafe', CBTxt::T( 'Metacafe' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'liveleak', CBTxt::T( 'LiveLeak' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'gametrailers', CBTxt::T( 'GameTrailers' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'hulu', CBTxt::T( 'Hulu' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'cnn', CBTxt::T( 'CNN' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'megavideo', CBTxt::T( 'Megavideo' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'blogtv', CBTxt::T( 'BlogTV' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'expotv', CBTxt::T( 'ExpoTV' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'g4tv', CBTxt::T( 'G4TV' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'revver', CBTxt::T( 'Revver' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'spike', CBTxt::T( 'Spike' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'mtv', CBTxt::T( 'MTV' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'stupidvideos', CBTxt::T( 'StupidVideos' ) );
		$providers[]	=	moscomprofilerHTML::makeOption( 'youku', CBTxt::T( 'Youku' ) );

		if ( isset( $value ) ) {
			$valAsObj	=	array_map( create_function( '$v', '$o=new stdClass(); $o->value=$v; return $o;' ), explode( '|*|', $value ) );
		} else {
			$valAsObj	=	null;
		}

		return moscomprofilerHTML::selectList( $providers, ( $control_name ? $control_name .'['. $name .'][]' : $name ), 'size="6" multiple="multiple"', 'value', 'text', $valAsObj, 0, true );
	}
}
?>