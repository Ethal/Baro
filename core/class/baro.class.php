<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class baro extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    public static function cron15() {
    //public static function cron() {
    //public static function cron15($_options) {
        foreach (eqLogic::byType('baro') as $baro) {
			log::add('baro', 'debug', 'pull cron');
			$baro->getInformations();
		}

    }


    /*     * *********************Methode d'instance************************* */

    public function preUpdate() {
    	if ($this->getConfiguration('pression') == '') {
    		throw new Exception(__('Le champ pression ne peut etre vide',__FILE__));
		}
    }

     public function postInsert() {
    	// Ajout d'une commande dans le tableau pour le dP/dT
        $BaroCmd = new BaroCmd();
        $BaroCmd->setName(__('dP/dT', __FILE__));
        $BaroCmd->setEqLogic_id($this->id);
		$BaroCmd->setLogicalId('dPdT');
        $BaroCmd->setConfiguration('data', 'dPdT');
        $BaroCmd->setType('info');
        $BaroCmd->setSubType('numeric');
        $BaroCmd->setUnite('hPa/h');
        $BaroCmd->setEventOnly(1);
		$BaroCmd->setIsHistorized(0);
		$BaroCmd->setIsVisible(0);
		$BaroCmd->setDisplay('generic_type','DONT');
        $BaroCmd->save();

        // Ajout d'une commande dans le tableau pour la pression
        $BaroCmd = new BaroCmd();
        $BaroCmd->setName(__('Pression', __FILE__));
        $BaroCmd->setEqLogic_id($this->id);
		$BaroCmd->setLogicalId('pression');
        $BaroCmd->setConfiguration('data', 'pression');
        $BaroCmd->setType('info');
        $BaroCmd->setSubType('numeric');
        $BaroCmd->setUnite('hPa');
        $BaroCmd->setEventOnly(1);
		$BaroCmd->setIsHistorized(0);
		$BaroCmd->setIsVisible(0);
		$BaroCmd->setDisplay('generic_type','WEATHER_PRESSURE');
        $BaroCmd->save();

		// Ajout d'une commande dans le tableau pour la tendance numérique
        $BaroCmd = new BaroCmd();
        $BaroCmd->setName(__('state', __FILE__));
        $BaroCmd->setEqLogic_id($this->id);
		$BaroCmd->setLogicalId('tendance_num');
        $BaroCmd->setConfiguration('data', 'tendance_num');
        $BaroCmd->setType('info');
        $BaroCmd->setSubType('numeric');
        $BaroCmd->setUnite('');
        $BaroCmd->setEventOnly(1);
		$BaroCmd->setIsHistorized(0);
		$BaroCmd->setIsVisible(0);
		$BaroCmd->setDisplay('generic_type','DONT');
        $BaroCmd->save();
        
        // Ajout d'une commande dans le tableau pour la tendance
        $BaroCmd = new BaroCmd();
        $BaroCmd->setName(__('Tendance', __FILE__));
        $BaroCmd->setEqLogic_id($this->id);
		$BaroCmd->setLogicalId('tendance');
        $BaroCmd->setConfiguration('data', 'tendance');
        $BaroCmd->setType('info');
        $BaroCmd->setSubType('string');
        $BaroCmd->setUnite('');
        $BaroCmd->setEventOnly(1);
		$BaroCmd->setIsHistorized(0);
		$BaroCmd->setIsVisible(1);
		$BaroCmd->setDisplay('generic_type','WEATHER_CONDITION');
        $BaroCmd->save();
    }

    /*     * **********************Getteur Setteur*************************** */
	public function postUpdate() {
        foreach (eqLogic::byType('baro') as $baro) {
            	$baro->getInformations();
		}
    }

    public function getInformations() {

    $idvirt = str_replace("#","",$this->getConfiguration('pression'));
    log::add('baro', 'debug', 'Configuration : $idvirt ' . $idvirt);
	$cmdvirt = cmd::byId($idvirt);
	
	if (is_object($cmdvirt)) {
		$pression = $cmdvirt->execCmd();
		//log::add('baro', 'debug', 'Configuration : pression ' . $pression);
	} else {
		log::add('baro', 'error', 'Configuration : pression non existante : ' . $this->getConfiguration('pression'));
	}

	// récupération du timestamp de la dernière mesure
	$histo = new scenarioExpression();
	$endDate = $histo -> collectDate($idvirt);
	
	// calcul du timestamp actuel
	$_date1 = new DateTime("$endDate");
	$_date2 = new DateTime("$endDate");
	$startDate = $_date1 -> modify('-15 minute');
	$startDate = $_date1 -> format('Y-m-d H:i:s');
	log::add('baro', 'debug', 'Calcul : $startDate ' . $startDate);
	log::add('baro', 'debug', 'Calcul : $endDate ' . $endDate);

	// dernière mesure barométrique
	$h1 = $histo->lastBetween($idvirt, $startDate, $endDate);
	log::add('baro', 'debug', 'Calcul : Pression actuelle ' . $h1 . ' hPa');

	// calcul du timestamp - 2h
	$endDate = $_date2 -> modify('-2 hour');
	$endDate = $_date2 -> format('Y-m-d H:i:s');
	$startDate = $_date1 -> modify('-2 hour');
	$startDate = $_date1 -> format('Y-m-d H:i:s');
	log::add('baro', 'debug', 'Calcul : $startDate ' . $startDate);
	log::add('baro', 'debug', 'Calcul : $endDate ' . $endDate);

	// mesure barométrique -2h
	$h2 = $histo->lastBetween($idvirt, $startDate, $endDate);
	log::add('baro', 'debug', 'Calcul : Pression -2 heures ' . $h2 . ' hPa');

	// calcul du timestamp - 4h
	$endDate = $_date2 -> modify('-2 hour');
	$endDate = $_date2 -> format('Y-m-d H:i:s');
	$startDate = $_date1 -> modify('-2 hour');
	$startDate = $_date1 -> format('Y-m-d H:i:s');
	log::add('baro', 'debug', 'Calcul : $startDate ' . $startDate);
	log::add('baro', 'debug', 'Calcul : $endDate ' . $endDate);

	// mesure barométrique -4h
	$h4 = $histo->lastBetween($idvirt, $startDate, $endDate);
	log::add('baro', 'debug', 'Calcul : Pression -4 heures ' . $h4 . ' hPa');

	// calculs de tendance
	// sources : http://www.freescale.com/files/sensors/doc/app_note/AN3914.pdf
    // et : https://www.parallax.com/sites/default/files/downloads/29124-Altimeter-Application-Note-501.pdf
    
	$tendance2h = ($h1 - $h2) / 2;
	log::add('baro', 'debug', 'Calcul : $tendance2h ' . $tendance2h . ' hPa/h');
	$tendance4h = ($h1 - $h4) / 4;
	log::add('baro', 'debug', 'Calcul : $tendance4h ' . $tendance4h . ' hPa/h');
	// moyennation de la tendance à -2h (50%) et -4h (50%)
	$tendance = (0.5 * $tendance2h + 0.5 * $tendance4h);
	$tendance_format = number_format($tendance, 3, '.', '');
	log::add('baro', 'debug', 'Calcul : $tendance moyennée ' . $tendance . ' hPa/h');

	if ($tendance > 2.5) {
	  // Quickly rising High Pressure System, not stable
	  $td = 'Forte embellie, instable';
	  $td_num=5;
	} elseif ($tendance > 0.5) {
	  	// Slowly rising High Pressure System, stable good weather
	  	$td = 'Amélioration, beau temps durable';
	  	$td_num=4;
	} elseif ($tendance > 0.0) {
	  	//  Stable weather condition
	  	$td = 'Lente amélioration, temps stable';
	  	$td_num=3;
	} elseif ($tendance > -0.5) {
	  	// Stable weather condition
	    $td = 'Lente dégradation, temps stable';
	    $td_num=2;
	} elseif ($tendance > -2.5) {
	  	// Slowly falling Low Pressure System, stable rainy weather
	  	$td = 'Dégradation, mauvais temps durable';
	  	$td_num=1;
	} else {
	  	// Quickly falling Low Pressure, Thunderstorm, not stable
	  	$td = 'Forte dégradation, instable';
	  	$td_num=0;
	}
	log::add('baro', 'debug', 'Calcul : $td ' . $td);
	log::add('baro', 'debug', 'Calcul : $td_num ' . $td_num);

	foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data')=="pression"){
					$cmd->setConfiguration('value', $pression);
					//$cmd->setIsVisible(true);
					$cmd->save();
					$cmd->event($pression);
					//log::add('baro', 'debug', 'Pression ' . $pression);
				}
				if($cmd->getConfiguration('data')=="dPdT"){
					$cmd->setConfiguration('value', $tendance_format);
					//$cmd->setIsVisible(true);
					$cmd->save();
					$cmd->event($tendance_format);
					//log::add('baro', 'debug', 'dPdT ' . $tendance);
				}
				if($cmd->getConfiguration('data')=="tendance"){
					$cmd->setConfiguration('value', $td);
					//$cmd->setIsVisible(true);
					$cmd->save();
					$cmd->event($td);
					//log::add('baro', 'debug', 'tendance ' . $td);
				}
				if($cmd->getConfiguration('data')=="tendance_num"){
					$cmd->setConfiguration('value', $td_num);
					//$cmd->setIsVisible(false);
					$cmd->save();
					$cmd->event($td_num);
					//log::add('baro', 'debug', 'tendance ' . $td);
				}			
		}
        return ;
    }
}

class BaroCmd extends cmd {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */
	public function execute($_options = null) {
	}

}

?>
