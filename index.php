<?php
/**
 * Created by PhpStorm.
 * User: carlos.andrade
 * Date: 24/11/2017
 * Time: 10:33
 */


$host = isset($_POST['host'])?$_POST['host']:'';
$domain = isset($_POST['domain'])?$_POST['domain']:'';
$login = isset($_POST['login'])?$_POST['login']:'';
$admin = isset($_POST['admin'])?$_POST['admin']:'';
$pass = isset($_POST['pass'])?$_POST['pass']:'';

include 'ldap.php';

$ldap = new Ldap($host,$domain, $admin,$pass);
$users = $ldap->getUsers();
//var_dump($users);
//$ldap->getUser();
?>

<html>
<head>
    <title>LDAP UTIL</title>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap4.min.css">
</head>
<body>
<div class="container">
    <form action="" method="post">
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label for="host">HOST LDAP</label>
                    <input type="text" class="form-control" id="host" name="host" placeholder="ex: 10.0.0.1" value="<?=$host?>">
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="domain">DOMAIN</label>
                    <input type="text" class="form-control" id="domain" name="domain" placeholder="meudomain.com.br" value="<?=$domain?>">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label for="admin">Administrator LDAP</label>
                    <input type="text" class="form-control" id="admin" name="admin" placeholder="Digite o administrador do AD" value="<?=$admin?>">
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label for="pass">Password</label>
                    <input type="password" class="form-control" id="pass" name="pass" placeholder="Senha do Admin" value="<?=$pass?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Conectar</button>

    </form>


   <?php if(sizeof($users)>0){ ?>
       <div class="table-responsive">
           <table id="ldaputil" class="table table-striped table-bordered table-hover">
               <thead>
               <tr>
                   <th>Name</th>
                   <th>Login</th>
                   <th>Criado em</th>
                   <th>Ultimo acesso</th>
                   <th>Proxima Troca</th>
                   <th>Desabilitada</th>
                   <th>Bloqueado</th>
                   <th>Açoes</th>
               </tr>
               </thead>
               <tbody>
               <?php foreach ($users as $u) { ?>
                   <tr>
                       <td><?=$u['nome']?></td>
                       <td><?=$u['login']?></td>
                       <td><?=date('d/m/Y H:i:s',strtotime($u['criado_em']))?></td>
                       <td><?=date('d/m/Y H:i:s',strtotime($u['ultimo_logon']))?></td>
                       <td><?=date('d/m/Y H:i:s',strtotime($u['proxima_troca_senha']))?></td>
                       <td><?=($u['conta_desabilitada']==0?"Não":"Sim")?></td>
                       <td><?=($u['block']=='0'?"Sim":"Não")?></td>
                       <td>X</td>
                   </tr>
               <?php } ?>
               </tbody>
           </table>
       </div>

    <?php } ?>
</div>


<script src="//code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#ldaputil').DataTable();
    } );
</script>
</body>
</html>
