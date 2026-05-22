<?php
// Use the autoloader from your setup
require_once 'SVGGraph/autoloader.php';

// --- Your exact URL parsing logic ---
$PASS=0; $ERROR=0; $FAIL=0; $SKIP=0;
$url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$parts = parse_url($url);
if(isset($parts['query'])) { parse_str($parts['query'], $query); }    
if(isset($query['PASS']) && is_numeric($query['PASS'])) { $PASS=$query['PASS']; }
if(isset($query['ERROR']) && is_numeric($query['ERROR'])) { $ERROR=$query['ERROR']; }
if(isset($query['FAIL']) && is_numeric($query['FAIL'])) { $FAIL=$query['FAIL']; }
if(isset($query['SKIP']) && is_numeric($query['SKIP'])) { $SKIP=$query['SKIP']; }

// --- Your exact graph settings, with legend removed ---
$settings = array(
  'back_colour' => 'lavender', 'stroke_colour' => '#000', 'back_stroke_width' => 0,
  'back_stroke_colour' => '#eee', 'pad_right' => 20, 'pad_left' => 20, 'pad_top' => 0, 
  'link_base' => '/', 'link_target' => '_top', 'show_labels' => true,
  'show_label_percent' => true, 'show_label_key' => false, 'label_font' => 'Arial',
  'label_font_size' => '12', 'label_colour' => '#DED', 'label_back_colour' => '#333',
  'label_position' => 0.70, 
  'show_legend' => false, // Set to false to remove the legend
  'aspect_ratio' => 0.6,
  'depth' => '10', 'keep_colour_order' => true,
);
$values = array('PASS' => $PASS, 'FAIL' => $FAIL, 'ERROR' => $ERROR, 'SKIP' => $SKIP);
$colors = array('#98fab2', '#A40808', '#f55c3d', '#dedede');
// This is no longer needed when show_legend is false, but we can leave it.
$settings['legend_entries'] = array('PASS', 'FAIL', 'ERROR', 'SKIP');

// --- Generate the SVG string in memory ---
$graph = new Goat1000\SVGGraph\SVGGraph(300, 180, $settings);
$graph->colours($colors);
$graph->values($values);
$svg_string = $graph->fetch('ExplodedPie3DGraph', false);

// --- Convert to PNG using ImageMagick Command-Line Interface ---

// Define paths for temporary files in a writable directory
$tmp_dir = sys_get_temp_dir();
$tmp_svg_path = $tmp_dir . DIRECTORY_SEPARATOR . uniqid('chart_', true) . '.svg';
$tmp_png_path = $tmp_dir . DIRECTORY_SEPARATOR . uniqid('chart_', true) . '.png';

try {
    // Write the SVG to a temporary file
    file_put_contents($tmp_svg_path, $svg_string);

    // Use the 'magick' command as you specified.
    $command = 'magick ' . escapeshellarg($tmp_svg_path) . ' ' . escapeshellarg($tmp_png_path);
    shell_exec($command);

    // Check if the output file was created and read it
    if (file_exists($tmp_png_path) && filesize($tmp_png_path) > 0) {
        $png_data = file_get_contents($tmp_png_path);

        // Output the final PNG image
        header("Content-Type: image/png");
        echo $png_data;
    } else {
        // Provide a more helpful error message
        $error_message = "ImageMagick command failed to create a PNG file.";
        $shell_output = shell_exec($command . ' 2>&1'); // Capture stderr
        if (!empty($shell_output)) {
            $error_message .= " Command output: " . $shell_output;
        }
        throw new Exception($error_message);
    }
} catch (Exception $e) {
    // Handle error if the command fails
    header("Content-Type: text/plain");
    echo "Error converting SVG to PNG: " . $e->getMessage();
} finally {
    // Clean up: delete the temporary files
    if (file_exists($tmp_svg_path)) {
        @unlink($tmp_svg_path);
    }
    if (file_exists($tmp_png_path)) {
        @unlink($tmp_png_path);
    }
}
?>
