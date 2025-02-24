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
require_once __DIR__ . '/../../core/php/core.inc.php';

class plugin {
	/*     * *************************Attributs****************************** */

	private $id;
	private $name;
	private $description;
	private $license;
	private $installation;
	private $author;
	private $require;
	private $requireOsVersion;
	private $category;
	private $filepath;
	private $index;
	private $display;
	private $mobile;
	private $eventjs;
	private $hasDependency;
	private $hasTtsEngine;
	private $maxDependancyInstallTime;
	private $hasOwnDeamon;
	private $issue = '';
	private $changelog = '';
	private $documentation = '';
	private $changelog_beta = '';
	private $documentation_beta = '';
	private $source = '';
	private $whiteListFolders = array();
	private $specialAttributes = array('object' => array(), 'user' => array());
	private $info = array();
	private $include = array();
	private $functionality = array();
	private $usedSpace = 0;
	private static $_cache = array();
	private static $_enable = null;

	/*     * ***********************Méthodes statiques*************************** */

	public static function byId($_id) {
		global $JEEDOM_INTERNAL_CONFIG;
		if (is_string($_id) && isset(self::$_cache[$_id])) {
			return self::$_cache[$_id];
		}
		if (!file_exists($_id) || strpos($_id, '/') === false) {
			$path = self::getPathById($_id);
		} else {
			$path = $_id;
		}
		if (!file_exists($path)) {
			self::forceDisablePlugin($_id);
			throw new Exception('Plugin introuvable : ' . $_id);
		}
		$data = json_decode(file_get_contents($path), true);
		if (!is_array($data)) {
			self::forceDisablePlugin($_id);
			throw new Exception('Plugin introuvable (json invalide) : ' . $_id . ' => ' . print_r($data, true));
		}
		$plugin = new plugin();
		$plugin->id = $data['id'];
		$plugin->name = $data['name'];
		$plugin->description = (isset($data['description'])) ? $data['description'] : '';
		if (is_array($plugin->description)) {
			if (isset($plugin->description[translate::getLanguage()])) {
				$plugin->description = $plugin->description[translate::getLanguage()];
			} else {
				$plugin->description = $plugin->description['fr_FR'];
			}
		}
		$plugin->license = (isset($data['licence'])) ? $data['licence'] : '';
		$plugin->license = (isset($data['license'])) ? $data['license'] : $plugin->license;
		$plugin->author = (isset($data['author'])) ? $data['author'] : '';
		$plugin->installation = (isset($data['installation'])) ? $data['installation'] : '';
		$plugin->hasDependency = (isset($data['hasDependency'])) ? $data['hasDependency'] : 0;
		$plugin->hasOwnDeamon = (isset($data['hasOwnDeamon'])) ? $data['hasOwnDeamon'] : 0;
		$plugin->hasTtsEngine = (isset($data['hasTtsEngine'])) ? $data['hasTtsEngine'] : 0;
		$plugin->maxDependancyInstallTime = (isset($data['maxDependancyInstallTime'])) ? $data['maxDependancyInstallTime'] : 30;
		$plugin->eventjs = (isset($data['eventjs'])) ? $data['eventjs'] : 0;
		$plugin->require = (isset($data['require'])) ? $data['require'] : '';
		$plugin->requireOsVersion = (isset($data['requireOsVersion'])) ? $data['requireOsVersion'] : '';
		$plugin->category = (isset($data['category'])) ? $data['category'] : '';
		$plugin->filepath = $path;
		$plugin->index = (isset($data['index'])) ? $data['index'] : $data['id'];
		$plugin->display = (isset($data['display'])) ? $data['display'] : '';
		$plugin->issue = (isset($data['issue'])) ? $data['issue'] : '';
		$plugin->whiteListFolders = (isset($data['whiteListFolders'])) ? $data['whiteListFolders'] : array();
		$plugin->changelog = (isset($data['changelog'])) ? str_replace('#language#', config::byKey('language', 'core', 'fr_FR'), $data['changelog']) : '';
		$plugin->documentation = (isset($data['documentation'])) ? str_replace('#language#', config::byKey('language', 'core', 'fr_FR'), $data['documentation']) : '';
		$plugin->changelog_beta = (isset($data['changelog_beta'])) ? str_replace('#language#', config::byKey('language', 'core', 'fr_FR'), $data['changelog_beta']) : '';
		$plugin->documentation_beta = (isset($data['documentation_beta'])) ? str_replace('#language#', config::byKey('language', 'core', 'fr_FR'), $data['documentation_beta']) : '';
		if (isset($data['specialAttributes'])) {

			if (isset($data['specialAttributes']['object'])) {
				$plugin->specialAttributes['object'] = $data['specialAttributes']['object'];
			}
			if (isset($data['specialAttributes']['user'])) {
				$plugin->specialAttributes['user'] = $data['specialAttributes']['user'];
			}
		}
		$plugin->mobile = '';
		if (file_exists(__DIR__ . '/../../plugins/' . $data['id'] . '/mobile/html')) {
			$plugin->mobile = (isset($data['mobile'])) ? $data['mobile'] : $data['id'];
		}
		if (isset($data['include'])) {
			$plugin->include = array(
				'file' => $data['include']['file'],
				'type' => $data['include']['type'],
			);
		} else {
			$plugin->include = array(
				'file' => $data['id'],
				'type' => 'class',
			);
		}
		$plugin->functionality['interact'] = array('exists' => method_exists($plugin->getId(), 'interact'), 'controlable' => 1);
		$plugin->functionality['cron'] = array('exists' => method_exists($plugin->getId(), 'cron'), 'controlable' => 1);
		$plugin->functionality['cron5'] = array('exists' => method_exists($plugin->getId(), 'cron5'), 'controlable' => 1);
		$plugin->functionality['cron10'] = array('exists' => method_exists($plugin->getId(), 'cron10'), 'controlable' => 1);
		$plugin->functionality['cron15'] = array('exists' => method_exists($plugin->getId(), 'cron15'), 'controlable' => 1);
		$plugin->functionality['cron30'] = array('exists' => method_exists($plugin->getId(), 'cron30'), 'controlable' => 1);
		$plugin->functionality['cronHourly'] = array('exists' => method_exists($plugin->getId(), 'cronHourly'), 'controlable' => 1);
		$plugin->functionality['cronDaily'] = array('exists' => method_exists($plugin->getId(), 'cronDaily'), 'controlable' => 1);
		$plugin->functionality['deadcmd'] = array('exists' => method_exists($plugin->getId(), 'deadCmd'), 'controlable' => 0);
		$plugin->functionality['health'] = array('exists' => method_exists($plugin->getId(), 'health'), 'controlable' => 0);
		if (!isset($JEEDOM_INTERNAL_CONFIG['plugin']['category'][$plugin->category])) {
			foreach ($JEEDOM_INTERNAL_CONFIG['plugin']['category'] as $key => $value) {
				if (!isset($value['alias'])) {
					continue;
				}
				if (in_array($plugin->category, $value['alias'])) {
					$plugin->category = $key;
					break;
				}
			}
		}
		$plugin->usedSpace = getDirectorySize(__DIR__ . '/../../plugins/' . $data['id']);
		self::$_cache[$plugin->id] = $plugin;
		return $plugin;
	}

	public static function forceDisablePlugin($_id) {
		config::save('active', 0, $_id);
		$values = array(
			'eqType_name' => $_id,
		);
		$sql = 'UPDATE eqLogic
		SET isEnable=0
		WHERE eqType_name=:eqType_name';
		DB::Prepare($sql, $values);
	}

	public static function getPluginPath($_pluginId) {
		return __DIR__ . '/../../plugins/' . $_pluginId;
	}

	public static function getPathById($_id) {
		return __DIR__ . '/../../plugins/' . $_id . '/plugin_info/info.json';
	}

	public function getPathToConfigurationById() {
		if (file_exists(__DIR__ . '/../../plugins/' . $this->id . '/plugin_info/configuration.php')) {
			return 'plugins/' . $this->id . '/plugin_info/configuration.php';
		} else {
			return '';
		}
	}

	public static function listPlugin($_activateOnly = false, $_orderByCategory = false, $_translate = true, $_nameOnly = false) {
		$listPlugin = array();
		if ($_activateOnly) {
			$sql = "SELECT plugin
			FROM config
			WHERE `key`='active'
			AND `value`='1'";
			$results = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
			if ($_nameOnly) {
				foreach ($results as $result) {
					$listPlugin[] = $result['plugin'];
				}
				return $listPlugin;
			}
			foreach ($results as $result) {
				try {
					$listPlugin[] = plugin::byId($result['plugin']);
				} catch (Exception $e) {
					log::add('plugin', 'error', $e->getMessage(), 'pluginNotFound::' . $result['plugin']);
				} catch (Error $e) {
					log::add('plugin', 'error', $e->getMessage(), 'pluginNotFound::' . $result['plugin']);
				}
			}
		} else {
			$rootPluginPath = __DIR__ . '/../../plugins';
			foreach (ls($rootPluginPath, '*') as $dirPlugin) {
				if (is_dir($rootPluginPath . '/' . $dirPlugin)) {
					$pathInfoPlugin = $rootPluginPath . '/' . $dirPlugin . 'plugin_info/info.json';
					if (!file_exists($pathInfoPlugin)) {
						continue;
					}
					if ($_nameOnly) {
						$listPlugin[] = str_replace('/', '', $dirPlugin);
					} else {
						try {
							$listPlugin[] = plugin::byId($pathInfoPlugin);
						} catch (Exception $e) {
							log::add('plugin', 'error', $e->getMessage(), 'pluginNotFound::' . $pathInfoPlugin);
						} catch (Error $e) {
							log::add('plugin', 'error', $e->getMessage(), 'pluginNotFound::' . $pathInfoPlugin);
						}
					}
				}
			}
			if ($_nameOnly) {
				return $listPlugin;
			}
		}
		if ($_orderByCategory) {
			$return = array();
			if (count($listPlugin) > 0) {
				foreach ($listPlugin as $plugin) {
					$category = $plugin->getCategory();
					if ($category == '') {
						$category = __('Autre', __FILE__);
					}
					if (!isset($return[$category])) {
						$return[$category] = array();
					}
					$return[$category][] = $plugin;
				}
				foreach ($return as &$category) {
					usort($category, 'plugin::orderPlugin');
				}
				ksort($return);
			}
			return $return;
		} else {
			if (isset($listPlugin) && is_array($listPlugin) && count($listPlugin) > 0) {
				usort($listPlugin, 'plugin::orderPlugin');
				return $listPlugin;
			} else {
				return array();
			}
		}
	}

	public static function getTranslation($_plugin, $_language) {
		if (in_array(trim($_plugin), array('', 'core', 'fr_FR', '.', '..'))) {
			return array();
		}
		$dir = __DIR__ . '/../../plugins/' . $_plugin . '/core/i18n';
		if (!file_exists($dir)) {
			@mkdir($dir, 0775, true);
		}
		if (!file_exists($dir)) {
			return array();
		}
		if (file_exists($dir . '/' . $_language . '.json')) {
			$return = file_get_contents($dir . '/' . $_language . '.json');
			return is_json($return, array());
		}
		return array();
	}

	public static function orderPlugin($a, $b) {
		$al = strtolower($a->name);
		$bl = strtolower($b->name);
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;
	}

	public static function heartbeat() {
		foreach (self::listPlugin(true) as $plugin) {
			try {
				$heartbeat = config::byKey('heartbeat::delay::' . $plugin->getId(), 'core', 0);
				if ($heartbeat == 0 || !is_numeric($heartbeat)) {
					continue;
				}
				$eqLogics = eqLogic::byType($plugin->getId());
				$ok = false;
				$enable = 0;
				foreach ($eqLogics as $eqLogic) {
					if (strtotime($eqLogic->getStatus('lastCommunication', date('Y-m-d H:i:s'))) > strtotime('-' . $heartbeat . ' minutes ' . date('Y-m-d H:i:s'))) {
						$ok = true;
						break;
					}
					if ($eqLogic->getIsEnable() == 1) {
						$enable++;
					}
				}
				if ($enable == 0) {
					continue;
				}
				if (!$ok) {
					$message = __('Attention le plugin', __FILE__) . ' ' . $plugin->getName();
					$message .= ' ' . __('n\'a pas recu de message depuis', __FILE__) . ' ' . $heartbeat . ' ' . __('min', __FILE__);
					$action = '<a href="/' . $plugin->getLinkToConfiguration() . '">' . __('Configuration', __FILE__) . '</a>';
					$logicalId = 'heartbeat' . $plugin->getId();
					message::add($plugin->getId(), $message, $action, $logicalId);
					if ($plugin->getHasOwnDeamon() && config::byKey('heartbeat::restartDeamon::' . $plugin->getId(), 'core', 0) == 1) {
						$plugin->deamon_start(true);
					}
				}
			} catch (Exception $e) {
			}
		}
	}

	public static function cron() {
		$cache = cache::byKey('plugin::cron::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cron::inprogress', -1);
			$cache = cache::byKey('plugin::cron::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cron n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cron::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cron::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cron::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cron')) {
				if (config::byKey('functionality::cron::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cron::last', $plugin_id);
				try {
					$plugin_id::cron();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cron::inprogress', 0);
	}

	public static function cron5() {
		$cache = cache::byKey('plugin::cron5::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cron5::inprogress', -1);
			$cache = cache::byKey('plugin::cron5::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cron5 n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cron5::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cron5::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cron5::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cron5')) {
				if (config::byKey('functionality::cron5::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cron5::last', $plugin_id);
				try {
					$plugin_id::cron5();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron5 du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron5 du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cron5::inprogress', 0);
	}

	public static function cron10() {
		$cache = cache::byKey('plugin::cron10::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cron10::inprogress', -1);
			$cache = cache::byKey('plugin::cron10::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cron10 n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cron10::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cron10::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cron10::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cron10')) {
				if (config::byKey('functionality::cron10::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cron10::last', $plugin_id);
				try {
					$plugin_id::cron10();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron10 du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron10 du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cron10::inprogress', 0);
	}

	public static function cron15() {
		$cache = cache::byKey('plugin::cron15::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cron15::inprogress', -1);
			$cache = cache::byKey('plugin::cron15::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cron15 n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cron15::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cron15::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cron15::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cron15')) {
				if (config::byKey('functionality::cron15::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cron15::last', $plugin_id);
				try {
					$plugin_id::cron15();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron15 du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron15 du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cron15::inprogress', 0);
	}

	public static function cron30() {
		$cache = cache::byKey('plugin::cron30::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cron30::inprogress', -1);
			$cache = cache::byKey('plugin::cron30::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cron30 n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cron30::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cron30::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cron30::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cron30')) {
				if (config::byKey('functionality::cron30::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cron30::last', $plugin_id);
				try {
					$plugin_id::cron30();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron30 du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cron30 du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cron30::inprogress', 0);
	}

	public static function cronDaily() {
		$cache = cache::byKey('plugin::cronDaily::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cronDaily::inprogress', -1);
			$cache = cache::byKey('plugin::cronDaily::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cronDaily n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cronDaily::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cronDaily::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cronDaily::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cronDaily')) {
				if (config::byKey('functionality::cronDaily::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cronDaily::last', $plugin_id);
				try {
					$plugin_id::cronDaily();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cronDaily du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cronDaily du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cronDaily::inprogress', 0);
	}

	public static function cronHourly() {
		$cache = cache::byKey('plugin::cronHourly::inprogress');
		if (is_array($cache->getValue(0))) {
			cache::set('plugin::cronHourly::inprogress', -1);
			$cache = cache::byKey('plugin::cronHourly::inprogress');
		}
		if ($cache->getValue(0) > 3) {
			$message = __('La tâche plugin::cronHourly n\'arrive pas à finir à cause du plugin :', __FILE__) . ' ' . cache::byKey('plugin::cronHourly::last')->getValue() . ' ' . __('nous vous conseillons de désactiver le plugin et de contacter l\'auteur', __FILE__);
			$action = '<a href="/index.php?v=d&p=plugin&id=' . cache::byKey('plugin::cronHourly::last')->getValue() . '">' . __('Configuration', __FILE__) . '</a>';
			message::add('core', $message, $action);
		}
		cache::set('plugin::cronHourly::inprogress', $cache->getValue(0) + 1);
		foreach (self::listPlugin(true) as $plugin) {
			if (method_exists($plugin->getId(), 'cronHourly')) {
				if (config::byKey('functionality::cronHourly::enable', $plugin->getId(), 1) == 0) {
					continue;
				}
				$plugin_id = $plugin->getId();
				cache::set('plugin::cronHourly::last', $plugin_id);
				try {
					$plugin_id::cronHourly();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cronHourly du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction cronHourly du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
		cache::set('plugin::cronHourly::inprogress', 0);
	}

	public static function start() {
		foreach (self::listPlugin(true) as $plugin) {
			$plugin->deamon_start(false, true);
			if (method_exists($plugin->getId(), 'start')) {
				$plugin_id = $plugin->getId();
				try {
					$plugin_id::start();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction start du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction start du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
	}

	public static function stop() {
		foreach (self::listPlugin(true) as $plugin) {
			$plugin->deamon_stop();
			if (method_exists($plugin->getId(), 'stop')) {
				$plugin_id = $plugin->getId();
				try {
					$plugin_id::stop();
				} catch (Exception $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction stop du plugin :', __FILE__) . ' ' . $e->getMessage());
				} catch (Error $e) {
					log::add($plugin_id, 'error', __('Erreur sur la fonction stop du plugin :', __FILE__) . ' ' . $e->getMessage());
				}
			}
		}
	}

	public static function checkDeamon() {
		foreach (self::listPlugin(true) as $plugin) {
			if (config::byKey('deamonAutoMode', $plugin->getId(), 1) != 1) {
				continue;
			}
			$dependancy_info = $plugin->dependancy_info();
			if ($dependancy_info['state'] == 'nok' && config::byKey('dependancyAutoMode', $plugin->getId(), 1) == 1) {
				try {
					$plugin->dependancy_install();
				} catch (Exception $e) {
				}
			} else if ($dependancy_info['state'] == 'in_progress' && $dependancy_info['duration'] > $plugin->getMaxDependancyInstallTime()) {
				if (isset($dependancy_info['progress_file']) && file_exists($dependancy_info['progress_file'])) {
					shell_exec('rm ' . $dependancy_info['progress_file']);
				}
				log::add($plugin->getId(), 'error', __('Attention : l\'installation des dépendances a dépassé le temps maximum autorisé :', __FILE__) . ' ' . $plugin->getMaxDependancyInstallTime() . 'min');
			}
			try {
				$plugin->deamon_start(false, true);
			} catch (Exception $e) {
			}
		}
	}

	/*     * *********************Méthodes d'instance************************* */

	public function report($_format = 'pdf', $_parameters = array()) {
		if ($this->getDisplay() == '') {
			throw new Exception(__('Vous ne pouvez pas faire de rapport sur un plugin sans panneau', __FILE__));
		}
		$url = network::getNetworkAccess('internal') . '/index.php?v=d&p=' . $this->getDisplay();
		$url .= '&m=' . $this->getId();
		$url .= '&report=1';
		if (isset($_parameters['theme']) && $_parameters['theme'] != '') {
			$url .= '&theme=' . $_parameters['theme'];
		}
		if (isset($_parameters['arg']) && trim($_parameters['arg']) != '') {
			$url .= '&' . $_parameters['arg'];
		}
		return report::generate($url, 'plugin', $this->getId(), $_format, $_parameters);
	}

	public function isActive() {
		if (self::$_enable === null) {
			self::$_enable = config::getPluginEnable();
		}
		if (isset(self::$_enable[$this->id])) {
			return self::$_enable[$this->id];
		}
		return 0;
	}

	public function callInstallFunction($_function, $_direct = false) {
		if (!$_direct) {
			return $this->launch($_function, true);
		}
		if (strpos($_function, 'pre_') !== false) {
			log::add('plugin', 'debug', 'Recherche de ' . __DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/pre_install.php');
			if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/pre_install.php')) {
				log::add('plugin', 'debug', __('Fichier d\'installation trouvé pour  :', __FILE__) . ' ' . $this->getId());
				require_once __DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/pre_install.php';
				ob_start();
				$function = $this->getId() . '_' . $_function;
				if (function_exists($this->getId() . '_' . $_function)) {
					$function();
				}
				return ob_get_clean();
			}
		} else {
			log::add('plugin', 'debug', 'Recherche de ' . __DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/install.php');
			if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/install.php')) {
				log::add('plugin', 'debug', __('Fichier d\'installation trouvé pour  :', __FILE__) . ' ' . $this->getId());
				require_once __DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/install.php';
				ob_start();
				$function = $this->getId() . '_' . $_function;
				if (function_exists($this->getId() . '_' . $_function)) {
					$function();
				}
				return ob_get_clean();
			}
		}
	}

	public function dependancy_info($_refresh = false) {
		$plugin_id = $this->getId();
		$cache = cache::byKey('dependancy' . $this->getID());
		if ($_refresh) {
			$cache->remove();
		} else {
			$return = $cache->getValue();
			if (is_array($return) && $return['state'] == 'ok') {
				return $cache->getValue();
			}
		}
		if (file_exists(__DIR__ . '/../../plugins/' . $plugin_id . '/plugin_info/packages.json')) {
			$return = array('log' => $plugin_id . '_packages');
			$packages = system::checkAndInstall(json_decode(file_get_contents(__DIR__ . '/../../plugins/' . $plugin_id . '/plugin_info/packages.json'), true), false, false, $plugin_id);
			$has_dep_to_install = false;
			foreach ($packages as $package => $info) {
				if ($info['status'] != 0 || $info['optional']) {
					continue;
				}
				$has_dep_to_install = true;
			}
			$return['state'] = ($has_dep_to_install) ? 'nok' : 'ok';
			$return['progress_file'] = '/tmp/jeedom_install_in_progress_' . $plugin_id;
			if (file_exists($return['progress_file'])) {
				$progression = trim(file_get_contents($return['progress_file']));
				if ($progression != '') {
					$return['progression'] = $progression;
				}
				$return['state'] = 'in_progress';
				if (config::byKey('lastDependancyInstallTime', $plugin_id) == '') {
					config::save('lastDependancyInstallTime', date('Y-m-d H:i:s'), $plugin_id);
				}
				$return['duration'] = round((strtotime('now') - strtotime(config::byKey('lastDependancyInstallTime', $plugin_id))) / 60);
			} else {
				$return['duration'] = -1;
			}
			$return['last_launch'] = config::byKey('lastDependancyInstallTime', $this->getId(), __('Inconnue', __FILE__));
			$return['auto'] = config::byKey('dependancyAutoMode', $this->getId(), 1);
			if ($return['state'] != 'in_progress' && method_exists($plugin_id, 'additionnalDependancyCheck')) {
				$additionnal = $plugin_id::additionnalDependancyCheck();
				if (isset($additionnal['state'])) {
					$return['state'] = $additionnal['state'];
				}
			}
			if ($return['state'] == 'ok') {
				cache::set('dependancy' . $this->getID(), $return);
			}
			return $return;
		}
		if ($this->getHasDependency() != 1 || !method_exists($plugin_id, 'dependancy_info')) {
			return array('state' => 'nok', 'log' => 'nok');
		}
		$return = $plugin_id::dependancy_info();
		if (!isset($return['log'])) {
			$return['log'] = '';
		}
		if (isset($return['progress_file'])) {
			$return['progression'] = 0;
			if (@file_exists($return['progress_file'])) {
				$return['state'] = 'in_progress';
				$progression = trim(file_get_contents($return['progress_file']));
				if ($progression != '') {
					$return['progression'] = $progression;
				}
			}
		}
		if ($return['state'] == 'in_progress') {
			if (config::byKey('lastDependancyInstallTime', $plugin_id) == '') {
				config::save('lastDependancyInstallTime', date('Y-m-d H:i:s'), $plugin_id);
			}
			$return['duration'] = round((strtotime('now') - strtotime(config::byKey('lastDependancyInstallTime', $plugin_id))) / 60);
		} else {
			$return['duration'] = -1;
		}
		$return['last_launch'] = config::byKey('lastDependancyInstallTime', $this->getId(), __('Inconnue', __FILE__));
		$return['auto'] = config::byKey('dependancyAutoMode', $this->getId(), 1);
		if ($return['state'] == 'ok') {
			cache::set('dependancy' . $this->getID(), $return);
		}
		return $return;
	}
	/**
	 *
	 * @return null
	 * @throws Exception
	 */
	public function dependancy_install($_force = false, $_foreground  = false) {
		$plugin_id = $this->getId();
		if (!$_force && config::byKey('dontProtectTooFastLaunchDependancy') == 0 && abs(strtotime('now') - strtotime(config::byKey('lastDependancyInstallTime', $plugin_id))) <= 60) {
			$cache = cache::byKey('dependancy' . $this->getID());
			$cache->remove();
			throw new Exception(__('Vous devez attendre au moins 60 secondes entre deux lancements d\'installation de dépendances', __FILE__));
		}
		$dependancy_info = $this->dependancy_info(true);
		if ($dependancy_info['state'] == 'in_progress') {
			throw new Exception(__('Les dépendances sont déjà en cours d\'installation', __FILE__));
		}
		if (file_exists(__DIR__ . '/../../plugins/' . $plugin_id . '/plugin_info/packages.json')) {
			$this->deamon_stop();
			config::save('lastDependancyInstallTime', date('Y-m-d H:i:s'), $plugin_id);
			system::checkAndInstall(json_decode(file_get_contents(__DIR__ . '/../../plugins/' . $plugin_id . '/plugin_info/packages.json'), true), true, $_foreground, $plugin_id, $_force);
			$cache = cache::byKey('dependancy' . $this->getID());
			$cache->remove();
			return;
		}
		if ($this->getHasDependency() != 1 || !method_exists($plugin_id, 'dependancy_install')) {
			return;
		}
		foreach (self::listPlugin(true) as $plugin) {
			if ($plugin->getId() == $this->getId()) {
				continue;
			}
			$dependancy_info = $plugin->dependancy_info();
			if ($dependancy_info['state'] == 'in_progress') {
				throw new Exception(__('Les dépendances d\'un autre plugin sont déjà en cours, veuillez attendre qu\'elles soient finies :', __FILE__) . ' ' . $plugin->getId());
			}
		}
		$cmd = $plugin_id::dependancy_install();
		if (is_array($cmd) && count($cmd) == 2) {
			$script = str_replace('#stype#', system::get('type'), $cmd['script']);
			$script_array = explode(' ', $script);
			if (file_exists($script_array[0])) {
				if (jeedom::isCapable('sudo')) {
					$this->deamon_stop();
					$message = __('Attention : installation des dépendances lancée', __FILE__);
					$action = '<a href="/index.php?v=d&p=plugin&id=' . $plugin_id . '">' . __('Configuration', __FILE__) . '</a>';
					message::add($plugin_id, $message, $action);
					config::save('lastDependancyInstallTime', date('Y-m-d H:i:s'), $plugin_id);
					if (exec('which at | wc -l') == 0) {
						exec(system::getCmdSudo() . '/bin/bash ' . $script . ' >> ' . $cmd['log'] . ' 2>&1 &');
					} else {
						if (!file_exists($cmd['log'])) {
							touch($cmd['log']);
						}
						exec('echo "/bin/bash ' . $script . ' >> ' . $cmd['log'] . ' 2>&1" | ' . system::getCmdSudo() . ' at now');
					}
					sleep(1);
				} else {
					log::add($plugin_id, 'error', __('Veuillez exécuter le script :', __FILE__) . ' /bin/bash ' . $script);
				}
			} else {
				log::add($plugin_id, 'error', __('Aucun script ne correspond à votre type de Linux :', __FILE__) . ' ' . $cmd['script'] . ' ' . __('avec #stype# :', __FILE__) . ' ' . system::get('type'));
			}
		}
		$cache = cache::byKey('dependancy' . $this->getID());
		$cache->remove();
		return;
	}

	public function dependancy_changeAutoMode($_mode) {
		config::save('dependancyAutoMode', $_mode, $this->getId());
		$plugin_id = $this->getId();
		if (method_exists($plugin_id, 'dependancy_changeAutoMode')) {
			$plugin_id::dependancy_changeAutoMode($_mode);
		}
		$this->dependancy_info(true);
	}

	public function deamon_changeAutoMode($_mode) {
		config::save('deamonAutoMode', $_mode, $this->getId());
		$plugin_id = $this->getId();
		if (method_exists($plugin_id, 'deamon_changeAutoMode')) {
			$plugin_id::deamon_changeAutoMode($_mode);
		}
	}
	/**
	 *
	 * @return array
	 */
	public function deamon_info() {
		$plugin_id = $this->getId();
		if ($this->getHasOwnDeamon() != 1 || !method_exists($plugin_id, 'deamon_info')) {
			return array('launchable_message' => '', 'launchable' => 'nok', 'state' => 'nok', 'log' => 'nok', 'auto' => 0);
		}
		$return = $plugin_id::deamon_info();
		if ($this->getHasDependency() == 1 && method_exists($plugin_id, 'dependancy_info') && $return['launchable'] == 'ok') {
			$dependancy_info = $this->dependancy_info();
			if ($dependancy_info['state'] != 'ok') {
				$return['launchable'] = 'nok';
				if ($dependancy_info['state'] == 'in_progress') {
					$return['launchable_message'] = __('Dépendances en cours d\'installation', __FILE__);
				} else {
					$return['launchable_message'] = __('Dépendances non installées', __FILE__);
				}
			}
		}
		if (!isset($return['launchable_message'])) {
			$return['launchable_message'] = '';
		}
		if (!isset($return['log'])) {
			$return['log'] = '';
		}
		$return['auto'] = config::byKey('deamonAutoMode', $this->getId(), 1);
		if ($return['auto'] == 0) {
			$return['launchable_message'] = __('Gestion automatique désactivée', __FILE__);
		}
		if (config::byKey('enableCron', 'core', 1, true) == 0) {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = __('Les crons et démons sont désactivés', __FILE__);
		}
		if (!jeedom::isStarted()) {
			$return['launchable'] = 'nok';
			$return['launchable_message'] = __('Jeedom n\'est pas encore démarré', __FILE__);
		}
		$return['last_launch'] = config::byKey('lastDeamonLaunchTime', $this->getId(), __('Inconnue', __FILE__));
		return $return;
	}

	public function deamon_start($_forceRestart = false, $_auto = false) {
		$plugin_id = $this->getId();
		if ($_forceRestart) {
			$this->deamon_stop();
		}
		try {
			if ($this->getHasOwnDeamon() == 1 && method_exists($plugin_id, 'deamon_info')) {
				$deamon_info = $this->deamon_info();
				if ($deamon_info['state'] == 'ok' && config::byKey('deamonRestartNumber', $plugin_id, 0) != 0) {
					config::save('deamonRestartNumber', 0, $plugin_id);
				}
				if ($_auto && $deamon_info['auto'] == 0) {
					return;
				}
				if ($deamon_info['launchable'] == 'ok' && $deamon_info['state'] == 'nok' && method_exists($plugin_id, 'deamon_start')) {
					$inprogress = cache::byKey('deamonStart' . $this->getId() . 'inprogress');
					$info = $inprogress->getValue(array('datetime' => strtotime('now') - 60));
					$info['datetime'] = (isset($info['datetime'])) ? $info['datetime'] : strtotime('now') - 60;
					if (abs(strtotime('now') - $info['datetime']) < 45) {
						if ($_auto) {
							return;
						}
						if (config::byKey('dontProtectTooFastLaunchDeamony') == 0) {
							throw new Exception(__('Vous devez attendre au moins 45 secondes entre deux lancements du démon. Dernier lancement :', __FILE__) . ' ' . date("Y-m-d H:i:s", $info['datetime']));
						}
					}
					if (config::byKey('deamonRestartNumber', $plugin_id, 0) > 3) {
						log::add($plugin_id, 'error', __('Attention je pense qu\'il y a un soucis avec le démon que j\'ai relancé plus de 3 fois consécutivement', __FILE__));
					}
					if (!$_forceRestart) {
						config::save('deamonRestartNumber', config::byKey('deamonRestartNumber', $plugin_id, 0) + 1, $plugin_id);
					}
					cache::set('deamonStart' . $this->getId() . 'inprogress', array('datetime' => strtotime('now')));
					config::save('lastDeamonLaunchTime', date('Y-m-d H:i:s'), $plugin_id);
					$fct = new ReflectionMethod($plugin_id, 'deamon_start');
					if ($fct->getNumberOfRequiredParameters() > 0) {
						$plugin_id::deamon_start($_auto);
					} else {
						$plugin_id::deamon_start();
					}
				}
			}
		} catch (Exception $e) {
			log::add($plugin_id, 'error', __('Erreur sur la fonction deamon_start du plugin :', __FILE__) . ' ' . $e->getMessage());
		} catch (Error $e) {
			log::add($plugin_id, 'error', __('Erreur sur la fonction deamon_start du plugin :', __FILE__) . ' ' . $e->getMessage());
		}
	}

	public function deamon_stop() {
		$plugin_id = $this->getId();
		try {
			if ($this->getHasOwnDeamon() == 1 && method_exists($plugin_id, 'deamon_info')) {
				$deamon_info = $this->deamon_info();
				if ($deamon_info['state'] == 'ok' && method_exists($plugin_id, 'deamon_stop')) {
					$plugin_id::deamon_stop();
				}
			}
		} catch (Exception $e) {
			log::add($plugin_id, 'error', __('Erreur sur la fonction deamon_stop du plugin :', __FILE__) . ' ' . $e->getMessage());
		} catch (Error $e) {
			log::add($plugin_id, 'error', __('Erreur sur la fonction deamon_stop du plugin :', __FILE__) . ' ' . $e->getMessage());
		}
	}

	public function setIsEnable($_state, $_force = false, $_foreground = false) {
		if (version_compare(jeedom::version(), $this->getRequire()) == -1 && $_state == 1) {
			throw new Exception(__('Votre version de Jeedom n\'est pas assez récente pour activer ce plugin', __FILE__));
		}
		$osVersion = $this->getRequireOsVersion();
		$distrib = system::getDistrib();
		if(isset($osVersion)){
			if ($distrib == 'debian' && version_compare(system::getOsVersion(), $osVersion) == -1 && $_state == 1) {
				throw new Exception(__('Votre version Debian n\'est pas assez récente pour activer cette version du plugin, '.$osVersion.' minimum demandé', __FILE__));
			}
		}
		$alreadyActive = config::byKey('active', $this->getId(), 0);
		if ($_state == 1) {
			config::save('active', $_state, $this->getId());
		}
		$deamonAutoState = config::byKey('deamonAutoMode', $this->getId(), 1);
		config::save('deamonAutoMode', 0, $this->getId());
		if ($_state == 0) {
			$eqLogics = eqLogic::byType($this->getId());
			if (is_array($eqLogics)) {
				foreach ($eqLogics as $eqLogic) {
					try {
						$eqLogic->setConfiguration('previousIsEnable', $eqLogic->getIsEnable());
						$eqLogic->setConfiguration('previousIsVisible', $eqLogic->getIsVisible());
						$eqLogic->setIsEnable(0);
						$eqLogic->setIsVisible(0);
						$eqLogic->save();
					} catch (Exception $e) {
					} catch (Error $e) {
					}
				}
			}
			$listeners = listener::byClass($this->getId());
			if (is_array($listeners)) {
				foreach ($listeners as $listener) {
					$listener->remove();
				}
			}
		} else if ($alreadyActive == 0 && $_state == 1) {
			try {
				include_file('core', $this->getId(), 'class', $this->getId());
			} catch (Exception $e) {
			} catch (Error $e) {
			}
			foreach (eqLogic::byType($this->getId()) as $eqLogic) {
				try {
					$eqLogic->setIsEnable($eqLogic->getConfiguration('previousIsEnable', 1));
					$eqLogic->setIsVisible($eqLogic->getConfiguration('previousIsVisible', 1));
					$eqLogic->save();
				} catch (Exception $e) {
				} catch (Error $e) {
				}
			}
		}
		try {
			if ($_state == 1) {
				log::add($this->getId(), 'info', 'Début d\'activation du plugin');
				$this->deamon_stop();

				$deamon_info = $this->deamon_info();
				sleep(1);
				log::add($this->getId(), 'info', 'Info sur le démon : ' . json_encode($deamon_info));
				if ($deamon_info['state'] == 'ok') {
					$this->deamon_stop();
				}
				if ($alreadyActive == 1) {
					$out = $this->callInstallFunction('update');
				} else {
					$out = $this->callInstallFunction('install');
				}
				$dependancy_info = $this->dependancy_info(true);
				if ($dependancy_info['state'] == 'nok' && config::byKey('dependancyAutoMode', $this->getId(), 1) == 1) {
					try {
						$this->dependancy_install($_force, $_foreground);
					} catch (Exception $e) {
					}
				}
			} else {
				$this->deamon_stop();
				if ($alreadyActive == 1) {
					$out = $this->callInstallFunction('remove');
				}
				rrmdir(jeedom::getTmpFolder($this->getId()));
			}
			if (isset($out) && trim($out) != '') {
				log::add($this->getId(), 'info', "Installation/remove/update result : " . $out);
			}
		} catch (Exception $e) {
			config::save('active', $alreadyActive, $this->getId());
			log::add('plugin', 'error', $e->getMessage());
			throw $e;
		} catch (Error $e) {
			config::save('active', $alreadyActive, $this->getId());
			log::add('plugin', 'error', $e->getMessage());
			throw $e;
		}
		if ($_state == 0) {
			config::save('active', $_state, $this->getId());
		}
		if ($deamonAutoState) {
			config::save('deamonAutoMode', 1, $this->getId());
		}
		if ($alreadyActive == 0 && $_state == 1) {
			config::save('log::level::' . $this->getId(), '{"100":"0","200":"0","300":"0","400":"0","1000":"0","default":"1"}');
		}
		self::$_enable = null;
		return true;
	}

	public function launch($_function, $_callInstallFunction = false) {
		if ($_function == '') {
			throw new Exception('La fonction à lancer ne peut être vide');
		}
		if (!$_callInstallFunction && (!class_exists($this->getId()) || !method_exists($this->getId(), $_function))) {
			throw new Exception('Il n\'existe aucune méthode : ' . $this->getId() . '::' . $_function . '()');
		}
		$cmd = __DIR__ . '/../../core/php/jeePlugin.php ';
		$cmd .= ' plugin_id=' . $this->getId();
		$cmd .= ' function=' . $_function;
		$cmd .= ' callInstallFunction=' . $_callInstallFunction;
		if (jeedom::checkOngoingThread($cmd) > 0) {
			return true;
		}
		log::add($this->getId(), 'debug', __('Lancement de :', __FILE__) . ' ' . $cmd);
		if ($_callInstallFunction) {
			return system::php($cmd . ' >> /dev/null 2>&1');
		} else {
			system::php($cmd . ' >> /dev/null 2>&1 &');
		}
		return true;
	}

	public function getUpdate() {
		return update::byTypeAndLogicalId('plugin', $this->getId());
	}

	public function getPathImgIcon() {
		if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/' . $this->getId() . '_icon.png')) {
			return 'plugins/' . $this->getId() . '/plugin_info/' . $this->getId() . '_icon.png';
		}
		if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/doc/images/' . $this->getId() . '_icon.png')) {
			return 'plugins/' . $this->getId() . '/doc/images/' . $this->getId() . '_icon.png';
		}
		if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/plugin_info/' . strtolower($this->getId()) . '_icon.png')) {
			return 'plugins/' . $this->getId() . '/plugin_info/' . strtolower($this->getId()) . '_icon.png';
		}
		if (file_exists(__DIR__ . '/../../plugins/' . $this->getId() . '/doc/images/' . strtolower($this->getId()) . '_icon.png')) {
			return 'plugins/' . $this->getId() . '/doc/images/' . strtolower($this->getId()) . '_icon.png';
		}
		return 'core/img/no-image-plugin.png';
	}

	public function getLogList() {
		$return = array();
		foreach (ls(log::getPathToLog(''), '*') as $log) {
			if ($log == $this->getId()) {
				$return[] = $log;
				continue;
			}
			if (strpos($log, $this->getId()) === 0) {
				$return[] = $log;
				continue;
			}
		}
		return $return;
	}

	public function getLinkToConfiguration() {
		return 'index.php?v=d&p=plugin&id=' . $this->getId();
	}

	public static function getConfigForCommunity($_separator = '<br>') {
		// retrieve core version and branch
		$infoCore = 'Core : ' . config::byKey('version', 'core', '#NA#') . ' (' . config::byKey('core::branch') . ')' . $_separator;

		// check if connexion used jeedom DNS
		$url =  network::getNetworkAccess('external');
		$hasDns  = ((strpos($url, 'jeedom.com') !== false || strpos($url, 'eu.jeedom.link')) !== false);
		$infoCore .= 'DNS ' . config::byKey('product_name') . ' : ' . ($hasDns ? __('oui', __FILE__) : __('non', __FILE__));
		$infoCore .= $_separator;

		return $infoCore;
	}

	/*     * **********************Getteur Setteur*************************** */

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getDescription() {
		if (is_array($this->description)) {
			foreach ($this->description as $key => &$value) {
				$value = nl2br($value);
			}
			return $this->description;
		}
		return nl2br($this->description);
	}

	public function getSpecialAttributes() {
		return $this->specialAttributes;
	}

	public function getInfo($_name = '', $_default = '') {
		if (count($this->info) == 0) {
			$update = update::byLogicalId($this->id);
			if (is_object($update)) {
				$this->info = $update->getInfo();
				if (is_object($update)) {
					$this->source = $update->getSource();
				}
			}
		}
		if ($_name !== '') {
			if (isset($this->info[$_name])) {
				return $this->info[$_name];
			}
			return $_default;
		}
		return $this->info;
	}

	public function getAuthor() {
		return $this->author;
	}

	public function getRequire() {
		return $this->require;
	}

	public function getRequireOsVersion() {
		return $this->requireOsVersion;
	}

	public function getCategory() {
		return $this->category;
	}

	public function getLicense() {
		return $this->license;
	}

	public function getFilepath() {
		return $this->filepath;
	}

	public function getInstallation() {
		return nl2br($this->installation);
	}

	public function getIndex() {
		return $this->index;
	}

	public function getInclude() {
		return $this->include;
	}

	public function getDisplay() {
		return $this->display;
	}

	public function setDisplay($display) {
		$this->display = $display;
		return $this;
	}

	public function getMobile() {
		return $this->mobile;
	}

	public function setMobile($mobile) {
		$this->mobile = $mobile;
		return $this;
	}

	public function getEventjs() {
		return $this->eventjs;
	}

	public function setEventjs($eventjs) {
		$this->eventjs = $eventjs;
		return $this;
	}

	public function getHasDependency() {
		return $this->hasDependency;
	}

	public function setHasDependency($hasDependency) {
		$this->hasDependency = $hasDependency;
		return $this;
	}

	public function getHasOwnDeamon() {
		return $this->hasOwnDeamon;
	}

	public function setHasOwnDeamony($hasOwnDeamon) {
		$this->hasOwnDeamon = $hasOwnDeamon;
		return $this;
	}

	public function getHasTtsEngine() {
		return $this->hasTtsEngine;
	}

	public function setHasTtsEngine($hasTtsEngine) {
		$this->hasTtsEngine = $hasTtsEngine;
		return $this;
	}

	public function getMaxDependancyInstallTime() {
		return $this->maxDependancyInstallTime;
	}

	public function setMaxDependancyInstallTime($maxDependancyInstallTime) {
		$this->maxDependancyInstallTime = $maxDependancyInstallTime;
		return $this;
	}

	public function getIssue() {
		return $this->issue;
	}

	public function setIssue($issue) {
		$this->issue = $issue;
		return $this;
	}

	public function getChangelog() {
		if ($this->changelog == '') {
			return $this->getInfo('changelog');
		}
		return $this->changelog;
	}

	public function setChangelog($changelog) {
		$this->changelog = $changelog;
		return $this;
	}

	public function getDocumentation() {
		if ($this->documentation == '') {
			return $this->getInfo('doc');
		}
		return $this->documentation;
	}

	public function setDocumentation($documentation) {
		$this->documentation = $documentation;
		return $this;
	}

	public function getChangelog_beta() {
		if ($this->changelog_beta == '') {
			return $this->getInfo('changelog_beta');
		}
		return $this->changelog_beta;
	}

	public function setChangelog_beta($changelog_beta) {
		$this->changelog_beta = $changelog_beta;
		return $this;
	}

	public function getDocumentation_beta() {
		if ($this->documentation_beta == '') {
			return $this->getInfo('doc_beta');
		}
		return $this->documentation_beta;
	}

	public function setDocumentation_beta($documentation_beta) {
		$this->documentation_beta = $documentation_beta;
		return $this;
	}

	public function getSource() {
		if ($this->source == '') {
			$update = update::byLogicalId($this->id);
			if (is_object($update)) {
				$this->source = $update->getSource();
			}
		}
		return $this->source;
	}

	public function getWhiteListFolders() {
		return $this->whiteListFolders;
	}

	public function setWhiteListFolders($paths) {
		$this->whiteListFolders = (array) $paths;
		return $this;
	}
}
