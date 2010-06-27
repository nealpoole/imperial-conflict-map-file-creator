<?php

if (isset($_POST['submit']))
{
	// Get the input and sanitize
	$galaxy_id = isset($_POST['g_id']) ? intval($_POST['g_id']) : 0;
	$systems_x = isset($_POST['x_coord']) ? intval($_POST['x_coord']) : 0;
	$systems_y = isset($_POST['y_coord']) ? intval($_POST['y_coord']) : 0;
	$sectors_x = isset($_POST['x_sectors']) ? intval($_POST['x_sectors']) : 0;
	$sectors_y = isset($_POST['y_sectors']) ? intval($_POST['y_sectors']) : 0;
	$description = isset($_POST['filename']) ? substr($_POST['filename'], 0, 32) : 'No information';

	// Validation is key!
	if ($galaxy_id <= 0)
		die('Invalid galaxy ID');

	if ($systems_x <= 0)
		die('Invalid horizontal coordinate count');

	if ($systems_y <= 0)
		die('Invalid vertical coordinate count');

	if ($sectors_x <= 0)
		die('Invalid horizontal sector count');

	if ($sectors_y <= 0)
		die('Invalid vertical sector count');

	// First, we fill the systems array with 0s
	$systems = array_fill(0, $systems_x * $systems_y, 0);

	// Next, we mark all the systems that exist
	for ($i = 1; $i <= $sectors_x; ++$i)
	{
		for ($j = 1; $j <= $sectors_y; ++$j)
		{
			$map_data = file_get_contents('http://www.imperialconflict.com/maps/'.$galaxy_id.'.'.$i.'.'.$j.'.html');

			$systems_found = array();
			preg_match_all('/onmousedown\=w\(([0-9]+)\,([0-9]+)\)/', $map_data, $systems_found);

			for ($k = 0; $k < count($systems_found[1]); ++$k)
			{
				$system_index = (($systems_found[2][$k] - 1) * $systems_x) + ($systems_found[1][$k] - 1);
				$systems[$system_index] = 1;
			}
		}
	}

	// Finally, we grab family data and mark all the homesystems
	$families = array();
	$family_data = strip_tags(file_get_contents('http://www.imperialconflict.com/rankings.php?type=topfamilies_size&g='.$galaxy_id));

	$families_found = array();
	preg_match_all('/[0-9]+.*?\(([0-9]+)\).*?\[([0-9]+),([0-9]+)\]/s', $family_data, $families_found);

	for ($i = 0; $i < count($families_found[1]); ++$i)
	{
		$system_index = (($families_found[3][$i] - 1) * $systems_x) + ($families_found[2][$i] - 1);

		$families[$families_found[1][$i]] = $system_index;
		$systems[$system_index] = 129;
	}


	// Yay for binary file formats
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.str_replace('"', '\'', $description).'.dat";');

	echo "ICg3"; # Header!
	echo pack('V', $systems_x); # The map size in the x direction
	echo pack('V', $systems_y); # The map size in the y direction
	echo pack('V', $sectors_x); # The number of sectors in the x direction
	echo pack('a32', $description); # The description
	echo pack('a64', 'http://www.imperialconflict.com/system.php?x=%d&y=%d'); # url to system page

	// Pack in the system data
	foreach ($systems as $system_info)
		echo pack('C', $system_info);

	// The number of families
	echo pack('V', count($families));

	// The family homesystems and corresponding family numbers
	foreach ($families as $homesystem_info)
		echo pack('V', $homesystem_info);
	foreach ($families as $family_number => $homesystem_info)
		echo pack('V', $family_number);

	// The number of sectors in the y direction
	echo pack('V', $sectors_y);
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<title>Semi-Automatic Map File Creator</title>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	</head>
	<body>
		<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post" enctype="multipart/form-data">
			Horizontal coordinate count: <input type="text" name="x_coord" /><br />
			Vertical coordinate count: <input type="text" name="y_coord" /><br />
			Horizontal sector count: <input type="text" name="x_sectors" /><br />
			Vertical sector count: <input type="text" name="y_sectors" /><br />
			Galaxy Description: <input type="text" name="filename" /><br />
			Galaxy ID: <input type="text" name="g_id" /><br />
			<input type="submit" name="submit" value="Create!" />
		</form>
	</body>
</html>
