<?php
/* Copyright (C) 2013	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2013	Herve Prot		<herve.prot@symeos.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace datatables\elements;

use datatables\ElementInterface;

class Render implements ElementInterface {

	protected $field;
	protected $name;
	protected $classname;
	protected $cardname;

	/* ______________________________________________________________________ */

	public function __construct($field = '', $name = '', $classname = '', $cardname = 'fiche') {
		$this->field = $field;
		$this->name = $name;
		$this->classname = $classname;
		$this->cardname = $cardname;
	}

	/* ______________________________________________________________________ */

	public function __toString() {
		return (string) $this->render();
	}

	/* ______________________________________________________________________ */

	public function render() {
		global $conf, $langs;

		$output = '';
		$type = (!empty($this->field->render->type) ? $this->field->render->type : $this->field->type);
		$ico = (!empty($this->field->render->ico) ? $this->field->render->ico : (!empty($this->field->ico) ? $this->field->ico : ''));
		$url = strtolower($this->classname) . '/'.$this->cardname.'.php?id=';

		switch ($type) {
			case "url" :
				if (!empty($this->field->render->url)) // default url
					$url = $this->field->render->url;

				$output.= 'function(data, type, row) {
								var ar = [];
								if(row._id === undefined)
									return ar.join("");
								else if(data === undefined)
									data = row._id;

								if (typeof data == "object") {
									if (data.length > 1) {
										$.each(data, function(key, value) {
											obj = value.id.split(":");
											var url = obj[0] + "/'. $this->cardname .'.php?id=" + value.id;
											ar[ar.length] = "<span class=\"' . $this->field->render->cssclass . '\"><a href=\"" + url + "\">" + value.name.toString() + "</a></span> ";
										});
									} else {
										obj = data.id.split(":");
										var url = obj[0] + "/'. $this->cardname .'.php?id=" + data.id;
										ar[ar.length] = "<span class=\"' . $this->field->render->cssclass . '\"><a href=\"" + url + "\">" + data.name.toString() + "</a></span> ";
									}
								} else {'."\n";

				if (!empty($ico)) {
					$title = $langs->trans("Show") . ' ' . $this->classname;
					$output.= 'ar[ar.length] = "<img src=\"theme/' . $conf->theme . '/img/ico/icSw2/' . $ico . '\" border=\"0\" alt=\"' . $title . ' : ";
								ar[ar.length] = data.toString() + "\" title=\"' . $title . ' : " + data.toString() + "\"> ";'."\n";
				}
				$output.= 'ar[ar.length] = "<a href=\"' . $url . '" + row._id + "\">" + data.toString() + "</a>";
							}
							return ar.join("");
						}';
				break;
			case "email" :
				$output.= 'function(data, type, row) {
								var ar = [];
								if(data === undefined)
									return ar.join("");

								ar[ar.length] = "<a href=\"mailto:" + data.toString() + "\">" + data.toString() + "</a>";
								return ar.join("");
							}';
				break;
			case "tag":
				$output.= 'function(data, type, row) {
								var ar = [];
								for (var i in data) {
									ar[ar.length] = "<span class=\"' . $this->field->render->cssclass . '\">" + data[i].toString() + "</span> ";
								}
								return ar.join("");
							}';
				break;
			case "status":
				$output.= 'function(data, type, row) {
								var now = Math.round(+new Date());
								var status = new Array();
								var expire = new Array();
								var statusDateEnd = "";';

					if (!empty($this->field->values)) {
						foreach ($this->field->values as $key => $aRow) {
							if (isset($aRow->label))
								$output.= 'status["' . $key . '"]= new Array("' . $langs->trans($aRow->label) . '","' . $aRow->cssClass . '");';
							else
								$output.= 'status["' . $key . '"]= new Array("' . $langs->trans($key) . '","' . $aRow->cssClass . '");';
							if (isset($aRow->dateEnd))
								$output.= 'expire["' . $key . '"]="' . $aRow->dateEnd . '";';
						}
					}

					// TODO show the data structure
					/*if (isset($params["dateEnd"])) {
						$rtr.= 'if(obj.aData.' . $params["dateEnd"] . ' === undefined)
				obj.aData.' . $params["dateEnd"] . ' = "";';
						$rtr.= 'if(obj.aData.' . $params["dateEnd"] . ' != ""){';
						$rtr.= 'var dateEnd = new Date(obj.aData.' . $params["dateEnd"] . ').getTime();';
						$rtr.= 'if(dateEnd < now)';
						$rtr.= 'if(expire[stat] !== undefined)
				stat = expire[stat];';
						$rtr.= '}';
					}*/

					$output.= 'var ar = [];
							ar[ar.length] = "<span class=\"tag " + status[data][1] + " glossy\">" + status[data][0] + "</span>";
							return ar.join("");
						}';
				break;
			case "action":
				$output.= 'function(data, type, row) {
								var ar = [];';

				foreach($this->field->action as $action => $param) {
					if ($action == 'edit')
				    	$output.= 'ar[ar.length] = \'<a href="' . $url . '\' + row._id + \'&action=' . $action . '&backtopage=' . $_SERVER['PHP_SELF'] . '" class="' . $param->cssclass . '" title="' . $langs->trans($param->label) . '"><img src="theme/' . $conf->theme . '/img/edit.png" alt="" /></a>\';';
					else if ($action == 'delete')
						$output.= 'ar[ar.length] = \'<a class="' . $param->cssclass . '" title="' . $langs->trans($param->label) . '"><img src="theme/' . $conf->theme . '/img/delete.png" alt="" /></a>\';';
				}

				$output.= 'return ar.join("");
							}';
				break;
			default :
				$output.= 'function(data, type, row) {
								return data;
							}';
				break;
		}

		return $output;
	}
}