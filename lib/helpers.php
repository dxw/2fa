<?php

function twofa_json($data) {
  header('Content-Type: application/json');
  echo json_encode($data);
  exit(0);
}
