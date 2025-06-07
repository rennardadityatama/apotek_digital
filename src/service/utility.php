<?php
include 'connection.php';

function redirect(string $fileName, string $message = "", string $type = 'success'): void
{
  if (isset($message)) {
    $_SESSION[$type] = $message;
  }

  header('Location: ../' . $fileName);
  exit();
}

function base_url()
{
  // Determine the protocol
  $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";

  // Get the host name
  $host = $_SERVER['HTTP_HOST'];

  // Get the base directory
  $baseDir = dirname($_SERVER['SCRIPT_NAME']);

  // Combine to form the base URL
  $baseUrl = $protocol . $host . $baseDir;

  // Return the base URL
  return rtrim($baseUrl, '/'); // Remove trailing slash if necessary
}

function slugify($string)
{
  // Convert to lowercase
  $string = strtolower($string);

  // Remove special characters
  $string = preg_replace('/[^a-z0-9\s-]/', '', $string);

  // Replace spaces and multiple hyphens with a single hyphen
  $string = preg_replace('/[\s-]+/', '-', $string);

  // Trim hyphens from the beginning and end
  $string = trim($string, '-');

  return $string;
}

function debug($var)
{
  print_r($var);
  die;
}

function hummanDate($inputDate)
{
  return date("j F Y", strtotime($inputDate));
  // // Create a DateTime object from the input date
  // $dateTime = DateTime::createFromFormat('y-m-d', $inputDate);
  // // Check if the date was created successfully

  // if ($dateTime) {
  //   // Format the date to "d F Y"
  //   $formattedDate = $dateTime->format('d F Y');

  //   // Replace the English month with the Indonesian month
  //   $indonesianMonths = [
  //     'January' => 'Januari',
  //     'February' => 'Februari',
  //     'March' => 'Maret',
  //     'April' => 'April',
  //     'May' => 'Mei',
  //     'June' => 'Juni',
  //     'July' => 'Juli',
  //     'August' => 'Agustus',
  //     'September' => 'September',
  //     'October' => 'Oktober',
  //     'November' => 'November',
  //     'December' => 'Desember',
  //   ];

  //   // Replace the month in the formatted date
  //   // $formattedDate = str_replace(array_keys($indonesianMonths), array_values($indonesianMonths), $formattedDate);

  //   return $formattedDate; // Output: 15 Juni 2024
  // } else {
  //   return false;
  // }
}

function get_gravatar(
  $email,
  $size = 64,
  $default_image_type = 'mp',
  $force_default = false,
  $rating = 'g',
  $return_image = false,
  $html_tag_attributes = []
) {
  // Prepare parameters.
  $params = [
    's' => htmlentities($size),
    'd' => htmlentities($default_image_type),
    'r' => htmlentities($rating),
  ];
  if ($force_default) {
    $params['f'] = 'y';
  }

  // Generate url.
  $base_url = 'https://www.gravatar.com/avatar';
  $hash = hash('sha256', strtolower(trim($email)));
  $query = http_build_query($params);
  $url = sprintf('%s/%s?%s', $base_url, $hash, $query);

  // Return image tag if necessary.
  if ($return_image) {
    $attributes = '';
    foreach ($html_tag_attributes as $key => $value) {
      $value = htmlentities($value, ENT_QUOTES, 'UTF-8');
      $attributes .= sprintf('%s="%s" ', $key, $value);
    }

    return sprintf('<img src="%s" %s/>', $url, $attributes);
  }

  return $url;
}

function generateRandomString($length = 10)
{
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[random_int(0, $charactersLength - 1)];
  }

  return $randomString;
}
