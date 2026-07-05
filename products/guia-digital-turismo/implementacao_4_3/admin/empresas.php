<?php
$ID_PREFIX = 'emp';
$FILE = 'businesses.json';
$TITLE = 'Empresas';
$FOLDER = 'empresas';
$PRIMARY_LABEL = 'Nome';
$FIELDS = array(
    'nome' => 'Nome',
    'categoria' => 'Categoria',
    'descricao' => 'Descrição',
    'telefone' => 'Telefone',
    'whatsapp' => 'WhatsApp',
    'instagram' => 'Instagram',
    'site' => 'Site',
    'endereco' => 'Endereço',
    'maps_query' => 'Busca no Google Maps',
    'fonte_imagem' => 'Fonte/observação da imagem',
    'imagem_status' => 'Status da imagem',
    'imagem_fonte_url' => 'Link de origem da imagem',
    'imagem_credito' => 'Crédito da imagem',
    'imagem_autorizada' => 'Imagem autorizada?'
);
require 'crud.php';
?>
