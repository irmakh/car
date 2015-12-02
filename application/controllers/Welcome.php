<?php
defined('BASEPATH') OR exit('No direct script access allowed');


require_once(APPPATH.'/libraries/car.php');
use GuzzleHttp\Client;
use Sunra\PhpSimple\HtmlDomParser;
class Welcome extends CI_Controller {



	function run(){
		$stime = time();
		echo 'Started	:'. $stime.PHP_EOL;
		echo 'Getting Listings...'.PHP_EOL;
		$this->index();
		echo 'Generating Car Profile Urls';
		$this->generatelinks();
		echo 'Getting Car Details...'.PHP_EOL;
		$this->capturecarhtmls();
		echo 'Parsing...'.PHP_EOL;
		$this->parsecars();
		echo 'Calculating Pricing...'.PHP_EOL;
		$this->calculate();
		$endtime = time();

		echo 'Started	:'. $stime.PHP_EOL;
		echo 'Ended	:'.$endtime.PHP_EOL;

	}


	public function index()
	{

		$listingUrl = 'http://www.sgcarmart.com/used_cars/listing.php';
		$num =0;

		for($num=0;$num<660;$num=$num+20){

		
			$parameters = [
				'BRSR'	=> 	$num,
				'CAT'	=>	18,
				'RPG'	=>	20,
				'AVL'	=>	2,
				'VEH'	=>	2,
				'RGD'	=>	9,
			];

			$url=$listingUrl.'?'. implode('&', array_map(function ($v, $k) { return $k . '=' . $v; }, $parameters, array_keys($parameters)));;
			echo 'Getting '.$url.PHP_EOL;
			$client = new Client();
			$response = $client->request('GET', $listingUrl, [
				'query'	=> $parameters
			]);


			$body = (string)$response->getBody();
			echo 'Done! '.PHP_EOL;
			echo 'Parsing... '.PHP_EOL;
			//var_dump($body);
			//exit;
			$html = HtmlDomParser::str_get_html($body);
			$listTable = $html->find("div#content table",3);
			echo 'Done! '.PHP_EOL;
			echo 'Writing to DB... '.PHP_EOL;

			$this->db->insert('rawhtml',[
				'url'	 => $url,
				'html' 	=> $listTable,
				'type'	=> 'list'
			]);

			echo 'Done '.PHP_EOL;
			//$query = $this->db->get('rawhtml');
		}
	}

	public function generatelinks(){
		$query = $this->db->query("SELECT * FROM rawhtml WHERE type ='list'");
		foreach($query->result() as $row){
			$html = HtmlDomParser::str_get_html($row->html);

			$links = $html->find('a');
			$toBeCaptured = [];
			foreach($links as $link){
				if(preg_match('/info.php\?ID=/',$link)){
					$toBeCaptured[md5($link->href)]=$link->href;
				}
			}

			$this->db->query("UPDATE rawhtml SET carlinks = '".serialize($toBeCaptured)."' WHERE id = '{$row->id}'");

		}
	}

	public function capturecarhtmls(){
		$query = $this->db->query("SELECT * FROM rawhtml WHERE type ='list' AND is_deleted = 0");
		$client = new Client();
		$listingUrl = 'http://www.sgcarmart.com/used_cars/';
		foreach($query->result() as $row){
			$carLinks = unserialize($row->carlinks);

			foreach($carLinks as $carLink){
				$url =  $listingUrl.$carLink;
				echo 'Getting '.$url.PHP_EOL;
				$response = $client->request('GET', $url);
				$body = (string)$response->getBody();

				echo 'Done! '.PHP_EOL;
				echo 'Parsing... '.PHP_EOL;

				$html = HtmlDomParser::str_get_html($body);
				$listTable = $html->find("div.box table",0);
				$listingname =  strip_tags($html->find("a.link_redbanner",0));

				echo 'Done! '.PHP_EOL;
				echo 'Writing to DB... '.PHP_EOL;

				$this->db->insert('rawhtml',[
						'url'	 => $url,
						'html' 	=> $listTable,
						'listingname' => $listingname,
						'type'	=> 'detail'
				]);
				echo 'Done '.PHP_EOL;


			}
			$this->db->query("UPDATE rawhtml SET is_deleted = '1' WHERE id = '{$row->id}'");

		}


	}

	public function parsecars(){
		echo 'Getting Raw Data From DB! '.PHP_EOL;
		$query = $this->db->query("SELECT * FROM rawhtml WHERE type ='detail'");
		$specs = [];
		echo 'Done! '.PHP_EOL;

		foreach($query->result() as $result){
			echo 'Parsing... '.PHP_EOL;
			$html = HtmlDomParser::str_get_html($result->html);

			$listTable = $html->find("tr");

			foreach($listTable as $tr){

					$trhtml = HtmlDomParser::str_get_html($tr);
					$td1 = $trhtml->find("td",0);
					$td2 = $trhtml->find("td",1);
					$specs[preg_replace("/ /","",strtolower(strip_tags($td1)))]= strip_tags($td2);


			}

			$carmartid = (int)preg_replace('/http:\/\/www.sgcarmart\.com\/used_cars\/info.php\?ID\=/','',$result->url);

			echo 'Done! '.PHP_EOL;
			echo 'Writing To DB... '.PHP_EOL;
			$mileage =  preg_replace("/km/","",preg_replace("/\,/","",$specs['mileage']));
			if($mileage < 150000){
				$this->db->insert('cars',[
						'price'	 => $specs['price'],
						'roadtax'	 => $specs['roadtax'],
						'transmission'	 => $specs['transmission'],
						'enginecap'	 => $specs['enginecap'],
						'power'	 => preg_replace("/                             	                                                                      /","",$specs['power']),
						'regdate'	 => $specs['regdate'],
						'mileage'	 => $mileage,
						'features'	 => $specs['features'],
						'accessories'	 => $specs['accessories'],
						'coe'	 => $specs['coe'],
						'omv'	 => $specs['omv'],
						'depreciation'	 => $specs['depreciation'],
						'noofowners'	 => $specs['no.ofowners'],
						'typeofveh'	 => $specs['typeofveh'],
						'carmartid'	 =>'http://www.sgcarmart.com/used_cars/info.php?ID='.$carmartid,
						'name'	=> $result->listingname
						//'stripped'	 => serialize($specs)

				]);
			}
			echo 'Done! '.PHP_EOL;
		}
	}


	function calculate(){

		$q = $this->db->query("SELECT * FROM cars");
		$c = new car();
		$this->db->query("UPDATE cars set
									cost_shipping =0,
									return_omv=0,
									return_roadtax=0,
									totalcost=0");
		foreach($q->result() as $r){
			if($r->price != '-'){
				$price = preg_replace("/[^0-9]/","",$r->price);
				$coe = preg_replace("/[^0-9]/","",$r->coe);
				$omv = preg_replace("/[^0-9]/","",$r->omv);
				$roadtax = preg_replace("/[^0-9]/","",substr($r->roadtax,0,strpos($r->roadtax,'/')));


				$c->setSalePrice(40000);
				$c->setDebug(true);
				$c->setCOE($coe);
				$c->setOMW($omv);
				$c->setRoadTaxPaid($roadtax);
				$c->setRegDate($r->regdate);
				$c->setContainerPrice(1182);
				$c->setAskedPrice($price);



				$c->calTotalCost();
				echo '----------COSTS--------'.PHP_EOL;
				echo 'Asked Price	: '.$c->getAskedPrice().PHP_EOL;
				echo 'Shipping	: '.$c->getShippingCost().PHP_EOL;
				echo '----------Returns-------'.PHP_EOL;
				echo 'OMV Return	:'.$c->getOmvReturn().PHP_EOL;
				echo 'Road Tax Return	:'.$c->getRoadTaxReturn().PHP_EOL;
				echo '----------Total----------'.PHP_EOL;
				echo 'Total Cost	: '.$c->getTotalCost().PHP_EOL;
				echo '------------------------------'.PHP_EOL;
				$this->db->query("UPDATE cars set
									cost_shipping = ".(int)$c->getShippingCost().",
									return_omv=".(int)$c->getOmvReturn().",
									return_roadtax=".(int)$c->getRoadTaxReturn().",
									totalcost=".(int)$c->getTotalCost()."
									WHERE id = {$r->id}");
			}
		}


	}

	function getukprice(){

		$query = $this->db->query("SELECT id,name FROM cars where id>186 ORDER BY id ASC LIMIT 1,1");
		$client = new Client();
		$listingUrl = 'http://www.ebay.co.uk/sch/i.html?_nkw='; //http://www.ebay.co.uk/sch/i.html?_nkw=Honda+Civic+1.8A&rt=nc&LH_BIN=1
		foreach($query->result() as $row) {

			$url =  $listingUrl.preg_replace('/ /','+',$row->name).'&rt=nc&LH_BIN=1&_sacat=9800&LH_PrefLoc=1';
			echo 'Getting '.$url.PHP_EOL;
			$response = $client->request('GET', $url);
			$body = (string)$response->getBody();

			echo 'Done! '.PHP_EOL;
			echo 'Parsing... '.PHP_EOL;

			$html = HtmlDomParser::str_get_html($body);

			$error = $html->find("p.sm-md",0);
			if($error == '<p class="sm-md">0 results found in the <b><a href="http://www.ebay.co.uk/sch/i.html?LH_BIN=1&_sacat=9800&_nkw=Mazda+3+GT&LH_PrefLoc=1&_blrs=category_constraint">Cars, Motorcycles & Vehicles</a></b> category, so we searched in all categories</p>'){
				echo 'not found!'.PHP_EOL;
				$ukprice = '£0';
			} else {

				$ukprice = strip_tags(preg_replace('/£/','',$html->find("li.prc span",0)));
				$ukprice += strip_tags(preg_replace('/£/','',$html->find("li.prc span",1)));
				$ukprice += strip_tags(preg_replace('/£/','',$html->find("li.prc span",2)));
				$ukprice += strip_tags(preg_replace('/£/','',$html->find("li.prc span",3)));
				$ukprice += strip_tags(preg_replace('/£/','',$html->find("li.prc span",4)));
				$ukprice += strip_tags(preg_replace('/£/','',$html->find("li.prc span",5)));

				$ukprice = $ukprice/6;
				if(preg_replace('/£/','',$ukprice) < 1000 )
				{
					$ukprice = strip_tags($html->find("li.prc span",1));
				}
			}


			echo $ukprice.PHP_EOL;

			echo 'Done! '.PHP_EOL;
			echo 'Writing to DB... '.PHP_EOL;

			$this->db->query("UPDATE cars set ukprice = '{$ukprice}' WHERE  id = {$row->id}");
			sleep(3);
		}
	}
}
