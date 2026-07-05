<?php
date_default_timezone_set('America/Sao_Paulo');
$admin_user='admin';
$admin_pass='PortalNews2026!';
$admin_pass_hash='';
$site_nome='TV Sumaré';
$site_url='https://tvsumare.com.br';


// Gemini Enterprise — chave exclusiva da TV Sumaré.
// Cole a chave abaixo ou configure a variável de ambiente GEMINI_API_KEY no servidor.
$gemini_api_key = getenv('GEMINI_API_KEY') ?: '';
$gemini_model = getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash';
$gemini_fallback_models = ['gemini-2.5-flash-lite'];
