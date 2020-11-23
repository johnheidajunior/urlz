<?PHP
	$pageClass = 'contact';
	$pageKeywords = 'Heida, John Heida, John Heida Junior, application, design, development, experienced, graphic design, interactive media, programmer, programming, versatile, web';
	$pageDescription = 'John Heida, John Heida Junior, Graphic Design, Interactive Media, Web Design and Development';
	$pageTitle = 'URLZ';
	
	include ('includes/alpha.php');
	
	require 'config_urlz.php';

	class URLZip 
	{
		public static $random_chars = 'abcdefghjkmnpqrstuvwxyz23456789';
		public static $valid_chars = 'abcdefghijklmnopqrstuvwxyz1234567890-_+~.';
		public static $slug_length = 7;

		protected $db;		
		var $s;
	
	    public function __construct(mysqli $db){
			$this->db = $db;			
			$this->s = (empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on')) ? 's' : '';
		}

		public function createURLZ($url) {
			$slug3 = '';
			$possible_slug = '';
			$numRows = 0;
			
			do {
				$possible_slug = '';
				for($i = 0; $i < self::$slug_length; $i++) {
					$possible_slug .= self::$random_chars[rand(0, strlen(self::$random_chars) - 1)];
				}

				$query="SELECT * FROM links WHERE slug = '$possible_slug'";
				$result= $this->db->query($query) or die(mysqli_connect_errno()."Data cannot....");

				$numRows = $result->num_rows;

				if($numRows == 0) {
					$slug3 = $possible_slug;
				}			
			} while(is_null($slug3));

			$escaped_url = $this->db->real_escape_string($url);

			$sql="INSERT INTO links (slug, url, visits, created) VALUES ('$slug3', '$escaped_url', 0, NOW())";
			$result2= $this->db->query($sql) or die(mysqli_connect_errno()."Data cannot be inserted....");

			if($result2){
				$redirect = "http".$this->s."://" . $_SERVER['HTTP_HOST'] . '/urlz2/' . $slug3. '/stats/';
				header("location:$redirect");	
			}
		}

		public function processURLZ() {
			$originalURL = '';
			
			$slug = str_replace("/urlz2/","",$_SERVER['REQUEST_URI']);
			$slug = str_replace("/stats/","",$slug);
			$slug = trim($slug, '/');

			$escaped_slug = $this->db->real_escape_string($slug);

			$query="SELECT url FROM links WHERE slug = '$escaped_slug'";
			$result= $this->db->query($query) or die(mysqli_connect_errno()."Data cannot1....");
			
			while ($row = $result->fetch_assoc()) {
				$originalURL =  $row["url"];
			}

			$query2="UPDATE links SET visits = visits + 1, last_visited = NOW() WHERE slug = '$escaped_slug'";		
			$result2= $this->db->query($query2) or die(mysqli_connect_errno()."Data cannot2....");

			if(isset($_SERVER['HTTP_REFERER'])) {
				$escaped_referer = $this->db->real_escape_string($_SERVER['HTTP_REFERER']);
			} else {
				$escaped_referer = '';
			}
			
			$query3="INSERT INTO visits (slug, visit_date, referer) VALUES ('$escaped_slug', NOW(), '$escaped_referer')";
			$result3= $this->db->query($query3) or die(mysqli_connect_errno()."Data cannot3....");

			header('Location: '.$originalURL);
		}

		public function displayURLZDashboard() {
			$data = array();	

			$query="SELECT * FROM links ORDER BY created DESC LIMIT 50";
		
			$links= $this->db->query($query) or die(mysqli_connect_errno()."Data cannot....");
			
			$numRows = $links->num_rows;

			if($numRows > 0){
				while ($link = $links->fetch_assoc()) {

					$reffering_domains = array();

					$query2="SELECT * FROM visits WHERE slug = '{$link['slug']}'";
					$visits= $this->db->query($query2) or die(mysqli_connect_errno()."Data cannot....");

					while ($visit = $visits->fetch_assoc()) {

						if(strlen($visit['referer']) > 0) {
							$url_info = parse_url($visit['referer']);
							
							$scheme = $url_info['scheme'];
							$host = $url_info['host'];
							$domain = $scheme . '://' . $host;
							if(is_null($reffering_domains[$domain])) {
								$reffering_domains[$domain] = 1;
							} else {
								$reffering_domains[$domain] = $reffering_domains[$domain] + 1;
							}					
						}
					}
					
					array_multisort($reffering_domains, SORT_DESC);
					
					if(count($reffering_domains) > 0) {
						$key = key($reffering_domains);
						$link['top_referer'] = array('domain' => $key, 'visits' => $reffering_domains[$key]);
					} else {
						$link['top_referer'] = null;
					}
					
					$data[] = $link;
				}				
			}

			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			echo "		<hr/><h1>URLZ Dashboard</h1><hr/>";
			echo '	</div>';
			echo '</div>';
			echo '<div class="row contactForm">';
			echo "	<div class='col-sm-2'><strong>Created</strong></div><div class='col-sm-2'><strong>Slug</strong></div><div class='col-sm-2'><strong>URL</strong></div><div class='col-sm-2'><strong>Visits</strong></div><div class='col-sm-2'><strong>Last Visit</strong></div><div class='col-sm-2'><strong>Top Referer</strong></div><div class='col-sm-2'></div>";
			echo '	</div>';
			echo '</div>';
			
			foreach($data as $link) {
				$short_url = "http".$this->s."://" . $_SERVER['HTTP_HOST'] . '/' . $link['slug'];
				echo '<div class="row contactForm">';
				echo "<div class='col-sm-2'>{$link['created']}</div>";
				echo "<div class='col-sm-2'><a href='$short_url'>{$link['slug']}</a></div>";
				echo "<div class='col-sm-2'><a href='{$link['url']}'>{$link['url']}</a></div>";
				echo "<div class='col-sm-2'>{$link['visits']}</div>";
				echo "<div class='col-sm-2'>{$link['last_visited']}</div>";
				if(is_null($link['top_referer'])) {
					echo "<div class='col-sm-2'>&nbsp;</div>";
				} else {
					$s = $link['top_referer']['visits'] == 1 ? '' : 's';
					echo "<div class='col-sm-2'><a href='" . $link['top_referer']['domain'] . "'>" . $link['top_referer']['domain'] . "</a> (" . $link['top_referer']['visits'] . " visit$s)</div>";
				}
				echo "<div class='col-sm-2'><a href='/urlz2/{$link['slug']}/stats/'>View Stats</a></div>";
				echo "</div>";
			}
		}

		public function displayURLZStats() {
			$slug = str_replace("/urlz2/","",$_SERVER['REQUEST_URI']);			
			$slug = str_replace("/stats/","",$slug);
			
			$escaped_slug = $this->db->real_escape_string($slug);
			
			$query="SELECT * FROM links WHERE slug = '$escaped_slug'";
			$result= $this->db->query($query) or die(mysqli_connect_errno()."Data cannot....");

			$data = array();
			$numRows = $result->num_rows;

			if($numRows > 0){
				$data['info'] = $result->fetch_array(MYSQLI_ASSOC);			
			}

			$data['visits'] = array();
			$reffering_domains = array();

			$visitsql="SELECT * FROM visits WHERE slug = '$escaped_slug'";
			$visits= $this->db->query($visitsql) or die(mysqli_connect_errno()."Data cannot....");

			$numRows2 = $visits->num_rows;

			if($numRows2 > 0){			
				while ($visit = $visits->fetch_assoc()) {
					if(strlen($visit['referer']) > 0) {
						$url_info = parse_url($visit['referer']);
						$scheme = $url_info['scheme'];
						$host = $url_info['host'];
						$domain = $scheme . '://' . $host;
						if(is_null($reffering_domains[$domain])) {
							$reffering_domains[$domain] = 1;
						} else {
							$reffering_domains[$domain] = $reffering_domains[$domain] + 1;
						}
					}
					$data['visits'][] = $visit;
				}
			}

			array_multisort($reffering_domains, SORT_DESC);
			
			$data['referers'] = $reffering_domains;

			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			echo "		<hr/><h1>URLZ</h1><hr/>";
			echo '	</div>';
			echo '</div>';
			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			echo "		<h3>Your zipped up URL for <a href='" . $data['info']['url'] . "'>" . $data['info']['url'] . "</a> has been created:</h3>";
			echo '	</div>';
			echo '</div>';
			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			echo "		<a href='https://www.johnheidajunior.com/urlz2/".$escaped_slug."'>https://www.johnheidajunior.com/urlz2/".$escaped_slug."</a><br /><br /><br /><br />";
			echo '	</div>';
			echo '</div>';
			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			echo "<h3>Stats for <a href='" . $data['info']['url'] . "'>" . $data['info']['url'] . "</a></h3>";
			echo "<ul>";
			echo "<li><strong>Created:</strong> " . $data['info']['created'] . "</li>";
			echo "<li><strong>Visits:</strong> " . $data['info']['visits'] . "</li>";
			echo "</ul>";
			echo '	</div>';
			echo '</div>';	
			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';	
			echo "<br /><h2>Top Referers</h2>";
			echo "<table><thead><td><strong>Domain</strong></td><td><strong>Visits</strong></td></thead><tbody>";
			
			foreach($data['referers'] as $domain => $visits) {
				echo "<tr>";
				echo "<td><a href='$domain'>" . $domain . "</a></td>";
				echo "<td>" . $visits . "</td>";
				echo "</tr>";
			}
			
			echo "</tbody></table>";
			echo '	</div>';
			echo '</div>';	
			echo '<div class="row contactForm">';
			echo '	<div class="col-xs-12 col-md-12 col-lg-12">';
			
			echo "<br /><h2>Recent Visits</h2>";
			echo "<table><thead><td><strong>Date</strong></td><td><strong>Referer</strong></td></thead><tbody>";
			
			foreach($data['visits'] as $visit) {
				echo "<tr>";
				echo "<td>" . $visit['visit_date'] . "</td>";
				echo "<td><a href='{$visit['referer']}'>{$visit['referer']}</a></td>";
				echo "</tr>";
			}
			
			echo "</tbody></table>";
			echo '	</div>';
			echo '</div>';	
		}
	}
?>

			<div class="row contactForm">
                <div class="col-xs-12 col-md-12 col-lg-12"> 
				<?php
                    $zipIt = new URLZip($dbConnection);
                
                    if(isset($_POST['url'])){
                        if (filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
                            $zipIt->createURLZ($_POST['url']);		
                        } else {
                            echo("Not a valid URL");
                        }
                    } else {
                        if(preg_match('!^/urlz2/stats(/?.*)$!', $_SERVER['REQUEST_URI']) == 1) {
                            $zipIt->displayURLZDashboard();
                            exit;
                        }
                        if(preg_match("!^/urlz2/[".$zipIt::$valid_chars."]+/?$!", $_SERVER['REQUEST_URI']) == 1) { 
                            $zipIt->processURLZ();
                        }
                        if(preg_match("!^/urlz2/[".$zipIt::$valid_chars."]+/stats/?$!", $_SERVER['REQUEST_URI']) == 1) { 
                            $zipIt->displayURLZStats();
                        }
                    }
                ?>
                </div> 
			</div>

			<div class="row contactForm">
                <div class="col-xs-12 col-md-12 col-lg-5"> 
                    <div class="subject-summary">	
                        <span class="subheader firstsub"><hr/><h1>URLZ</h1><hr/></span>
                        <h3>A prototype hybrid frankenstein URL shrinker zipper based on and sampled from prior internet works then rehashed and mashed into a pseudo-object oriented architecture.  Try it out!</h3>
                    </div>
                </div>		
                
                <div class="col-xs-12 col-md-12 col-lg-6 col-lg-offset-1">	
                	<div class="project-image-boxes">	
                        <form method="post" action="">
                            <div class="form-group">
                                <br /><br /><br /><br /><br /><br /><input type="text" class="form-control" name="url" placeholder="Paste your URL">
                            </div>
                            <div class="form-group">
                                <input type="submit" class="btn btn-info" value="Zip that URL!">
                            </div>
                        </form>
                	</div>
                </div> 
			</div>
            
<?php
	include ('includes/omega.php');
?>