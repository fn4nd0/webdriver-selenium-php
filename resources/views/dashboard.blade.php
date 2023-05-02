<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Seus estilos aqui */
    </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container">
        <button id="automation-btn" class="button">Automação</button>
        <a href="{{ route('convertPdfToCsv') }}" class="button">PDF</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var automationBtn = document.getElementById('automation-btn');

            automationBtn.addEventListener('click', function () {
                // Realiza uma chamada AJAX para a rota '/automacao'
                fetch('/automacao', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                })
                .then(function (response) {
                    if (response.ok) {
                        // Exibir mensagem de sucesso ou fazer algo com os dados retornados
                        console.log('Automação iniciada com sucesso.');
                    } else {
                        // Exibir mensagem de erro
                        console.log('Ocorreu um erro ao iniciar a automação.');
                    }
                })
                .catch(function (error) {
                    // Exibir mensagem de erro
                    console.log('Ocorreu um erro ao iniciar a automação:', error);
                });
            });
        });
    </script>
</body>
</html>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f8f8f8;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .container {
        text-align: center;
    }
    .button {
        background-color: #3490dc;
        border: none;
        color: white;
        padding: 15px 32px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        border-radius: 5px;
    }
    .button:hover {
        background-color: #2779bd;
    }
</style>
