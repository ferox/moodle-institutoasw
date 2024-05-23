<!-- LOGOS -->
<br />
<p align="center">
  <a>
    <img src="https://institutoasw.org/wp-content/uploads/2022/11/logo-site.png" alt="IASW Logo" width="400" height="200">
  </a>
  <a>
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/c/c6/Moodle-logo.svg/640px-Moodle-logo.svg.png" alt="Moodle Logo" width="400" height="150">
  </a>

<h3 align="center">Moodle LMS com Composer do IASW</h3>

  <p align="center">
    <a href="https://github.com/institutoasw/moodle-composer/compare">Melhorias via PR</a>
  </p>
</p>

<!--ÍNDICE -->
<details open="open">
  <summary>Índice</summary>
  <ol>
    <li>
      <a href="#sobre">Sobre</a>
      <ul>
        <li><a href="#tecnologias">Tecnologias</a></li>
      </ul>
    </li>
    <li>
      <a href="#iniciando">Iniciando</a>
      <ul>
        <li><a href="#requisitos">Requisitos</a></li>
      </ul>
    </li>
    <li>
      <a href="#passo-a-passo">Passo-a-passo</a>
      <ul>
        <li><a href="#instalando-lando">Instalando o Lando</a></li>
        <li><a href="#criando-os-containers-usando-o-lando">Criando os containers usando o Lando</a></li>
        <li><a href="#criando-arquivos-de-configurações">Criando arquivos de configurações</a></li>
        <li><a href="#instalando-o-moodle-através-do-composer">Instalando o moodle através do composer</a></li>
        <li><a href="#editando-as-variáveis-de-ambiente">Editando as variáveis de ambiente</a></li>
        <li><a href="#criando-o-diretório-dataroot">Criando o diretório dataroot</a></li>
        <li><a href="#acessando-o-projeto-pelo-navegador">Acessando o projeto pelo navegador</a></li>
      </ul>
    </li>
    <li><a href="#instalando-plugins-no-moodle-com-o-composer">Instalando plugins no moodle com o composer</a></li>
    <li><a href="#criando-novos-temas-para-o-moodle">Criando novos temas para o moodle</a></li>
    <li><a href="#licença">License</a></li>
  </ol>
</details>

<!-- SOBRE -->
## Sobre

Este projeto visa oferecer uma versão do Moodle gerenciada através do composer. O projeto é um fork do repositório criado pelo desenvolvedor Michael Meneses de Souza.

O repositório se encontra neste link: [moodle-composer](https://github.com/michaelmeneses/moodle-composer)

### Tecnologias

* [PHP 8](https://www.php.net/releases/8.0/pt_BR.php)
* [Apache 2](https://www.apache.org/)
* [MariaDB](https://mariadb.org/)
* [Lando](https://lando.dev/)


<!-- INICIANDO -->
## Iniciando

Este fork sofreu algumas alterações em sua arquitetura, sendo mais próximo da organização de diretórios do framework Laravel.

### Requisitos

Tenha em sua máquina o Docker e o Lando instalados:
* Docker version 26.1.3, build b72abbb
  ```sh
  docker -v
  ```
* Lando v3.21.0-beta.20
  ```sh
  lando version
  ```
Como instalar o Lando em sua máquina: [https://lando.dev/download/](https://lando.dev/download/)  

## Passo-a-passo

### Criando os containers usando o Lando

* Clone o repositório
  ```sh
  git clone https://github.com/institutoasw/moodle-composer.git
  ```
* Tenha certeza de que está dentro do diretório clonado, exemplo: ~/Projetos/Github.com/moodle-composer
  ```sh
  pwd
  ```
* Criando os containers
  ```sh
  lando start
  ```

### Criando arquivos de configurações

#### Na raiz do projeto você encontra os seguintes arquivos:

- .env.example
- config.example.php
- .htaccess.example

#### Renomeie eles, como mostrado abaixo:

- .env
- config.php
- .htaccess

### Instalando o moodle através do composer

* Instale através do lando
  ```sh
  lando composer install
  ```

### Editando as variáveis de ambiente

* Abra o arquivo .env e edite as seguintes variáveis
  ```sh
  MOODLE_DBTYPE='mariadb'
  MOODLE_DBHOST='database'
  MOODLE_DBNAME='lamp'
  MOODLE_DBUSER='lamp'
  MOODLE_DBPASS='lamp'
  
  MOODLE_WWWROOT='https://moodle-iasw-lms.lndo.site'
  
  MOODLE_DATAROOT='moodle-data'
  ```
### Criando o diretório dataroot

* Esse diretório salva arquivos de cache, de sessão, temporários, entre outros.
* Na raiz do projeto crie um diretório chamado moodle-data, ou o mesmo nome dado ao arquivo variável de ambiente criado na entrada MOODLE_DATAROOT no passo anterior.

### Acessando o projeto pelo navegador

[https://moodle-iasw-lms.lndo.site](https://moodle-iasw-lms.lndo.site)

### Instalando plugins no moodle com o composer

* Todos os pacotes do projeto são hospedados pelo Satis que é um gerador de respositórios estático.
* Site do repositório Satis brasileiro: [https://satis.middag.com.br](https://satis.middag.com.br)

* Para adicionar um pacote você deve adicionar o nome do pacote e a versão desejada no arquivo composer.json, dentro de require, como mostrado abaixo:

```json
  {
    "require": {
      "composer/installers": "~1.0",
      "vlucas/phpdotenv": "^5.6",
      "moodle/moodle": "4.4.*",
      "mdjnelson/moodle-mod_customcert": "2023042408"
    }
  }
  ```

* É possivel adicionar outras fontes de repositórios. Para isso, adicione em repositories no arquivo composer.json, como mostrado abaixo:

```json
  {
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/kiklop74/moodle-local_dompdf.git"
      }
    ]
  }
  ```
* Após adicionar o repositório, adicionei-o também no require, como mostrado abaixo:
```json
  {
    "require": {
      "composer/installers": "~1.0",
      "vlucas/phpdotenv": "^5.6",
      "moodle/moodle": "4.4.*",
      "mdjnelson/moodle-mod_customcert": "2023042408",
      "kiklop74/moodle-local_dompdf": "2021062801"
    }
  }
  ```

> IMPORTANTE: note que o formato da versão a ser instalada é o fornecido pelo repositório Satis, que pode ser encontrado em Releases.

* Depois dos ajustes no arquivo composer.json rode:

```sh
  lando composer update
  ```

### Criando novos temas para o moodle

* Com a nova arquitetura, os temas versionados estão dentro do diretório Themes em app. Após adicionar o novo tema ao seu projeto, rode seguinte comando:

```sh
  lando composer create-links
  ```

* Esse script irá criar um link simbólico de todos os temas de app/Themes para public/theme.

## Licença

[GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.en.html)
