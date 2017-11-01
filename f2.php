 
<?php2
|--------------------------------------------------------------------------
| Web Routesssss

dd

|--------------------------------------------------------------------------
|
|
*/


Route::get('/', function(){ s
});s

Route::get('/', function(){ 
    


Route::get('/redirecionado', function(){
    return 'Você foi redirecionado';
});


Route::view('/sobre', 'sobrenos');

Route::get('/parametros/{nome}/{snome?}', function($nome, $snome = null){ } );

Route::get('/testeid/{id}', function($fsdid){
    return 'O id digitado foi "'. $id . "'"; 
})->middleware('filtroIdade');

Route::get("/testecontroller/{id}/{nome}", "TesteController@teste")->middleware('filtroIdade');

/*dvds
|--------------------------------------------------------------------------
| Rotas Formulário
|--------------------------------------------------------------------------
*/
Route::get('/registro', 'CadastroController@Mostra');

Route::post('/registro', 'CadastroController@Registra');

Route::get('/usuarios', 'CadastroController@Consulta');;;;;


Route::get('/', function(){ 
    return view('welcome');
});



Route::get('/', function(){ 
    return view('welcome');
});



Route::get('/', function(){ 
    return view('welcome');
});
