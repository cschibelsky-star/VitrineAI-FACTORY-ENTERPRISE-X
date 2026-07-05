<?php
$ID_PREFIX = 'atr';
$FILE = 'attractions.json';
$TITLE = 'Atrativos';
$FOLDER = 'atrativos';
$PRIMARY_LABEL = 'Nome';
$FIELDS = array(
    'nome' => 'Nome',
    'slug' => 'Slug',
    'categoria' => 'Categoria',
    'descricao_curta' => 'Descrição curta',
    'descricao' => 'Descrição completa',
    'endereco' => 'Endereço',
    'horario' => 'Horário',
    'latitude' => 'Latitude',
    'longitude' => 'Longitude',
    'maps_query' => 'Busca no Google Maps',
    'fonte_imagem' => 'Fonte/observação da imagem',
    'imagem_status' => 'Status da imagem',
    'imagem_fonte_url' => 'Link de origem da imagem',
    'imagem_credito' => 'Crédito da imagem',
    'imagem_autorizada' => 'Imagem autorizada?'
);
require 'crud.php';
?>
