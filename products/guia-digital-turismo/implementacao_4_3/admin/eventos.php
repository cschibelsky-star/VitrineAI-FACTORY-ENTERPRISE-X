<?php
$ID_PREFIX = 'evt';
$FILE = 'events.json';
$TITLE = 'Eventos';
$FOLDER = 'eventos';
$PRIMARY_LABEL = 'Título';
$FIELDS = array(
    'titulo' => 'Título',
    'slug' => 'Slug',
    'categoria' => 'Categoria',
    'tipo' => 'Tipo/linha editorial',
    'data' => 'Data',
    'data_label' => 'Rótulo de data',
    'horario' => 'Horário',
    'local' => 'Local',
    'status' => 'Status interno',
    'gratis' => 'Entrada/valor',
    'descricao_curta' => 'Descrição curta',
    'descricao' => 'Descrição completa',
    'fonte_imagem' => 'Fonte/observação da imagem',
    'imagem_status' => 'Status da imagem',
    'imagem_fonte_url' => 'Link de origem da imagem',
    'imagem_credito' => 'Crédito da imagem',
    'imagem_autorizada' => 'Imagem autorizada?'
);
require 'crud.php';
?>
