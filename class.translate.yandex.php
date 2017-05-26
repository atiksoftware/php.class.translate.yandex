<?php


	namespace Yandex;

	class Translate
	{
		/**
		* Yandex Translate Api Key'ini Buraya Giriniz..!
		*/
		const api_key = 'xxxxxxxxxxxxxxxxxx374.ba5b355db14xxxxxxxxxxxx413b5f04aa7a38f359';

		/**
		* Sorgulama Sonucu D�necek Olan Verinin T�r�
		*/
		protected $return_type = 'tr.json';


		/**
		* Construct Method
		*/
		function __construct(){}

		/**
		* Sorgulama Sonras�nda D�necek Veri T�r�n� Set Eder...
		*
		* @param $tur
		*/
		public function set_return_type($tur)
		{
			if ($tur == 'xml')
			{
				$this->return_type = 'tr';
			}
			else
			{
				$this->return_type = 'tr.json';
			}
		}

		/**
		* Sorgulama Sonras�nda D�necek Veri T�r�n� Get Eder...
		*
		* @return string
		*/
		public function get_return_type()
		{
			return $this->return_type;
		}

		/**
		* Ba�lant� ��in Curl Fonksiyonu..
		*
		* @param string $url
		* @param string $send_type
		* @param array  $post
		*
		* @return bool
		* @throws Exception
		*/

		private function curl($url, $send_type = 'get', $post = array())
		{
			if (empty($url))
			{
				throw new Exception('Api Ba�lant� Hatas�: '. __FUNCTION__ .' i�in url de�i�keni girilmemi�');
			}

			if ($send_type != 'get' && count($post) == 0)
				throw new Exception('Curl Fonksiyonu ��in POST De�erleri Bulunamad�..!');

			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 6);
			if ($send_type != 'get')
			{
				curl_setopt($curl, CURLOPT_POST, TRUE);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			}
			$result = curl_exec($curl);

			if ($result)
			{
				return $this->is_error($result);
			}
			else
			{
				throw new Exception('Curl Ba�lant�s� Bir Hata ile Kar��la�t�. HATA: ' . curl_error($curl));
			}
		}

		/**
		* Yandex Translate Desteklenen Dilleri Sorgular...
		*
		* @param string $ui
		*
		* @return string
		*/

		public function api_translate_langs($ui = 'tr')
		{
			//destekleyen diller
			$supported_langs = array('en', 'tr', 'uk', 'ru');

			//desteklenmeyen bir aray�z dili sunulursa t�rk�e yapal�m...
			if ( empty($ui) || false == in_array($ui, $supported_langs))
				$ui = 'tr';

			//veri t�r�
			$return = $this->get_return_type();

			$api_url = 'https://translate.yandex.net/api/v1.5/'. $return .'/getLangs?key='. self::api_key .'&ui='. $ui;

			$langs = $this->curl($api_url);

			return $langs;
		}

		/**
		* Yandex Translate Fonksiyonu
		*
		* @param		$source
		* @param		$target
		* @param		$word
		* @param string $send_type
		*
		* @return bool
		* @throws Exception
		*/

		public function translate($source, $target, $word, $send_type = 'get')
		{
			if (empty($source))
				throw new Exception('Kaynak Dil Belirtilmedi..!');

			if (empty($target))
				throw new Exception('Hedef Dil Belirtilmedi..!');

			if (empty($word) || count($word) == 0)
				throw new Exception('�evirilecek Kelime Bulunamad�..!');

			//array gelen ifadeyi stringe �evirelim...
			if (is_array($word))
			{
				$text = $this->array2string($word);
			}
			else
			{
				$text = $word;
			}

			//veri t�r�n� alal�m..
			$return = $this->get_return_type();

			//send turu get ise..
			if ($send_type == 'get'){
				$api_url = 'https://translate.yandex.net/api/v1.5/'. $return .'/translate?key='. self::api_key .'&lang='. $source .'-'. $target .'&text='. $text .'';

				return $this->curl($api_url);
			}
			else
			{
				$api_url = 'https://translate.yandex.net/api/v1.5/'. $return .'/translate';

				$data =  $this->curl($api_url, 'post', array('key' => self::api_key, 'lang' => $source .'-'. $target, 'text' => $text));

				return $data;

			}
		}

		/**
		* Array'� String ifadeye �evirir..
		*
		* @param array $array
		*
		* @return string
		* @throws Exception
		*/
		private function array2string($array=array())
		{
			if (count($array) == 0)
				throw new Exception(__FUNCTION__ .' Fonksiyonuna Bo� Array De�i�keni G�nderemezsiniz..!');

			$string = '';

			foreach($array as $key => $val)
			{
				$string .= $val;
			}

			return $string;
		}

		/**
		* Hata Olup Olmad���na Bakar...
		*
		* @param $veri
		*
		* @return bool
		* @throws Exception
		*/

		private function is_error($veri)
		{
			//gelen verinin t�r�
			$return = $this->get_return_type();

			//json veri ise
			if ($return == 'tr.json')
			{
				//veriyi diziye �evirelim
				$json = json_decode($veri, TRUE);

				//code var m� bakal�m
				if ( false == array_key_exists('code', $json))
				{
					return $veri;
				}
				else
				{
					$code = $json['code'];

					if ($json['code'] != '200')
					{
						throw new Exception('Bir Hata Olu�tu. Hata Kodu : ' . $code . ' Hata A��klamas� : '. $this->error($code) );
					}
					else
					{
						return $veri;
					}
				}
			}
			else
			{
				//xml hatas� denetimi...
				if (stripos($veri, '<Error') > 0)
				{
					@preg_match_all('/<Error code="(.*?)" message="(.*?)"\/>/i', $veri, $output);

					$code = $output[1][0];

					throw new Exception('Bir Hata Olu�tu. Hata Kodu : ' . $code . ' Hata A��klamas� : '. $this->error($code) );

				}
				else
				{
					return $veri;
				}
			}

		}

		/**
		* API HATALARININ T�RK�ES�
		* @param $err_no
		*
		* @return mixed
		*/

		private function error($err_no)
		{
			$error_list = array(
				'401' => 'Yanl�� API Anahtar� Girdiniz..!',
				'402' => 'API Anahtar� Engellenmi�..!',
				'403' => 'G�nl�k Sorgulama S�n�r�n� Ge�tiniz..!',
				'404' => 'G�nl�k Sorgulama S�n�r�n� Ge�tiniz..!',
				'413' => '�evirelecek Kelime S�n�r� A��ld�..! Hat�rlatma : Max. 1000 Karakter',
				'422' => '�evrilemeyen Kelime',
				'501' => 'Desteklenmeyen Dil'
			);

			return $error_list[$err_no];
		}

	}
