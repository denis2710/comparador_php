<?php2

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function(){ 
    return view('welcome');
});


Route::redirect('/redirecionando', '/redirecionado', 301);

Route::get('/redirecionado', function(){
    return 'Você foi redirecionado';
});


Route::view('/sobre', 'sobrenos');

Route::get('/parametros/{nome}/{snome?}', function($nome, $snome = null){ 
    return 'Seja bem vindo ' . $nome . ' ' . $snome;
} );

Route::get('/testeid/{id}', function($id){
    return 'O id digitado foi "'. $id . "'"; 
})->middleware('filtroIdade');

Route::get("/testecontroller/{id}/{nome}", "TesteController@teste")->middleware('filtroIdade');

/*
|--------------------------------------------------------------------------
| Rotas Formulário
|--------------------------------------------------------------------------
*/
Route::get('/registro', 'CadastroController@Mostra');

Route::post('/registro', 'CadastroController@Registra');

Route::get('/usuarios', 'CadastroController@Consulta');