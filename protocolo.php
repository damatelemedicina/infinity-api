<?php 
   function getProtocolo($id) {
		$protocolo = "";
		for ($t=0; $t < 2; $t++) {
			$alfa = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$letras = "";
			for ($i = 0; $i < strlen($id); $i++) {
				$l = substr($alfa, rand(0, 25), 1);
				if (rand(0, 1) == 1) $l = strtolower($l);
				$letras .= $l;
			}
			for ($i = 0; $i < strlen($id); $i++) {
				if (rand(0, 1) == 1) $protocolo .= substr($id, $i, 1);
				else $protocolo .= substr($letras, $i, 1);
			}
		}
		return strtoupper($protocolo);
	}

echo getProtocolo(304);