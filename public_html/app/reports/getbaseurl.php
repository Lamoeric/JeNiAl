<?php
/**
 *  Returns the base URL from the configuration
 */
function getBaseUrl($mysqli) {
  $retVal = "";
  $query = "SELECT cpaurl FROM cpa_configuration where id = 1";
  $result = $mysqli->query($query);
  while ($row = $result->fetch_assoc()) {
    $retVal = $row['cpaurl'];
  }
  return $retVal;
}
