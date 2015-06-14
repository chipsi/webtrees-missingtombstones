<?php
/**
 * webtrees missingtombstone: online genealogy missing-tombstones-module.
 * Copyright (C) 2015 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// use WT_Date;
// use WT_Date_Gregorian;

class TombstoneSearch {
	
	private static function findMedia($person) {
		$media = array();
		$matches = array();
		
		preg_match_all('/\n(\d) OBJE @(' . WT_REGEX_XREF . ')@/', $person->getGedcom(), $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$media[] = WT_Media::getInstance($match[2]);
		}
		
		return $media;
	}
	
	private static function personHasTombstone($person) {
		$linkedMedia = static::findMedia($person);
		foreach ($linkedMedia as $media) {
			if ($media->getMediaType() === "tombstone") {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Perform the search
	 *
	 * @return array of individuals.
	 */
	public static function advancedSearch($startyear = null) {
		if (empty($startyear)) {
			$startyear = date("Y") - 30;
		}
		
		$myindilist = array();
		$bind = array();
		$date = WT_Date::parseDate($startyear);
	
		// Dynamic SQL query, plus bind variables
		$sql = "SELECT DISTINCT 
					ind.i_id AS xref, 
					ind.i_file AS gedcom_id, 
					ind.i_gedcom AS gedcom 
				FROM 
					`##individuals` ind
				JOIN 
					`##dates`  i_d ON (i_d.d_file=ind.i_file AND i_d.d_gid=ind.i_id)
				WHERE 
					ind.i_file=?
					AND i_d.d_fact='DEAT' 
					AND i_d.d_type='@#DGREGORIAN@' 
					AND i_d.d_julianday1>=?";
		$bind[] = WT_GED_ID;
		$bind[] = $date->minJD;

		$rows = WT_DB::prepare($sql)
				->execute($bind)->fetchAll();
		
		foreach ($rows as $row) {
			$person = WT_Individual::getInstance($row->xref, $row->gedcom_id, $row->gedcom);
			
			if (!static::personHasTombstone($person)) {
				$myindilist[] = $person;
			}
			
			// next one
		}
		
		return $myindilist;
	}
}

