{*
 * Copyright 2020 MDS Technologies (Pty) Ltd and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 *
 *  @author MDS Collivery <integration@collivery.co.za>
 *  @copyright  2020 MDS Technologies (Pty) Ltd
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{if $htmldata.suburbs && $htmldata.locationTypes && $htmldata.suburb && $htmldata.locationType}
<script>
	var suburbs      = $htmldata.suburbs,
		location_types = $htmldata.locationTypes,
		suburb         = $htmldata.suburb,
		location_type  = $htmldata.locationType;

	replaceText('State', 'Town');
	replaceText('City', 'Suburb');
	replaceText('Address (Line 2)', 'Location Type');

	addDropDownSuburb(suburbs, suburb);
	addDropDownLocationType(location_types, location_type);
</script>
{else}
  <p>Missing required data to deplay view..</p>
{/if}
