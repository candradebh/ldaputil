<?php

/**
 * Created by PhpStorm.
 * User: carlos.andrade
 * Date: 24/11/2017
 * Time: 11:12
 */
class Ldap
{
    protected $server;
    protected $domain;
    protected $admin;
    protected $password;


    /**
     * Ldap constructor.
     * @param $server
     * @param $domain
     * @param $admin
     * @param $password
     */
    public function __construct($server, $domain, $admin, $password)
    {
        $this->server = $server;
        $this->domain = $domain;
        $this->admin = $admin;
        $this->password = $password;
    }


    public function win2unix($wtime) {
        return ($wtime * 0.0000001) - 11644473600;
    }

    public function formmat_oz($oztime) {
        return substr($oztime, 0, 4) . "-" . substr($oztime, 4, 2) . "-" . substr($oztime, 6, 2) . " " . substr($oztime, 8, 2) . ":" . substr($oztime, 10, 2) . ":" . substr($oztime, 12, 2);
    }

    public function win2unixFmt($wtime) {
        return date('Y-m-d H:i:s', bcsub(bcdiv($wtime, '10000000'), '11644473600'));
    }

    public function unlock($id){
        $controlOption["useraccountcontrol"]['badpwdcount'][0] = '5';
        $mod = ldap_modify($this->server, $this->getDn(), $controlOption);
        var_dump($mod);
    }

    public function getIdentifier(){


    }
    public function getUser(){
        if(!($ldap = ldap_connect($this->server))) return false;

        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_SIZELIMIT, 0);

        $ldapbind = ldap_bind($ldap, $this->admin."@".$this->domain, $this->password);


        $base_dn = $this->getDn();

        // Lê a quantidade maxima de dias para troca de senha setada para o dominio

        $sr = ldap_read($ldap, $base_dn, 'objectclass=*', array('maxPwdAge'));
        $info = ldap_get_entries($ldap, $sr);
        $maxpwdage = $info[0]['maxpwdage'][0];

        // Localiza todos usuarios

        ldap_control_paged_result($ldap, 2000, true);//define para paginação de 2000
        $sr = ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person))");
        $info = ldap_get_entries($ldap, $sr);

        //var_dump($info[160]);
        //echo "Count: ". $info["count"];
        //var_dump($info[160]['badpwdcount']);
       /* foreach ($info[160] as $k=>$v){
            echo $k." - ".$v."\n";
            if($k=='useraccountcontrol'){
                foreach ($v as $k1=>$v1) echo $k1."=>".$v1."\n";
            }
        }*/

    }

    public function getDn(){
        $dc = explode(".", $this->domain);
        $base_dn = "";
        foreach($dc as $_dc) $base_dn .= "dc=".$_dc.",";


        return substr($base_dn, 0, -1);
    }

    public function getUsers() {

        // Conecta ao servidor AD

        if(!($ldap = ldap_connect($this->server))) return false;

        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_SIZELIMIT, 0);

        $ldapbind = ldap_bind($ldap, $this->admin."@".$this->domain, $this->password);


        $base_dn = $this->getDn();

        // Lê a quantidade maxima de dias para troca de senha setada para o dominio

        $sr = ldap_read($ldap, $base_dn, 'objectclass=*', array('maxPwdAge'));
        $info = ldap_get_entries($ldap, $sr);
        $maxpwdage = $info[0]['maxpwdage'][0];

        // Localiza todos usuarios

        ldap_control_paged_result($ldap, 2000, true);//define para paginação de 2000
        $sr = ldap_search($ldap, $base_dn, "(&(objectClass=user)(objectCategory=person))");
        $info = ldap_get_entries($ldap, $sr);

       // var_dump($info[160]);


        for($i = 0; $i < $info["count"]; $i++)
        {
            $users[$i]["nome"] = $info[$i]["cn"][0]." - ".$i;
            $users[$i]["login"] = $info[$i]["samaccountname"][0];
            $users[$i]["criado_em"] = $this->formmat_oz($info[$i]["whencreated"][0]);
            $users[$i]["alterado_em"] = $this->formmat_oz($info[$i]["whenchanged"][0]);
            $users[$i]["block"] = '0';
            if (isset($info[$i]["lastlogon"][0])) {
                $t = $this->win2unix($info[$i]["lastlogon"][0]);
                $users[$i]["ultimo_logon"] = date("Y-m-d h:i:s", $t);
            } else {
                $users[$i]["ultimo_logon"] = "";
            }

            $users[$i]["useraccountcontrol"] = $info[$i]["useraccountcontrol"][0];

            if ($info[$i]["useraccountcontrol"][0] & 2) {
                $users[$i]["conta_desabilitada"] = "1";
            } else {
                $users[$i]["conta_desabilitada"] = "0";
            }

            if ($info[$i]["useraccountcontrol"][0] & 65536) {
                $users[$i]["senha_nunca_expira"] = "1";
            } else {
                $users[$i]["senha_nunca_expira"] = "0";
            }

            if ($info[$i]["useraccountcontrol"][0] & 8388608) {
                $users[$i]["senha_expirada"] = "1";
            } else {
                $users[$i]["senha_expirada"] = "0";
            }
            if ($info[$i]["useraccountcontrol"][0]=='512'){
                $users[$i]["block"] = '1';
            }
           /* if (isset($info[$i]["useraccountcontrol"]['badpwdcount'])) {
                $users[$i]["block"]= $info[$i]["useraccountcontrol"]['badpwdcount'][0];
            }*/
            if (isset($info[$i]["memberof"][0])) {
                $saida = array();
                for ($z = 0; $z < sizeof($info[$i]["memberof"]) - 1; $z++) {
                    $tempo = explode(",", $info[$i]["memberof"][$z]);

                    for ($w = 0; $w < sizeof($tempo); $w++) {
                        if (strpos($tempo[$w], "DC=") === false) {
                            if (!in_array($tempo[$w], $saida)) {
                                $saida[] = $tempo[$w];
                            }
                        }
                    }
                }

                sort($saida);
                $users[$i]["grupos"] = $saida;
            } else {
                $saida = array();
                $users[$i]["grupos"] = $saida;
            }

            $users[$i]["nome_ad"] = $info[$i]["distinguishedname"][0];
            $users[$i]["ultima_troca_senha"] = $this->win2unixFmt($info[$i]["pwdlastset"][0]);

            if (($users[$i]["senha_nunca_expira"] != "0") || ($users[$i]["conta_desabilitada"] != "0")){
                $users[$i]["proxima_troca_senha"] = "0";
            } else {
                $users[$i]["proxima_troca_senha"] = $this->win2unixFmt(bcsub($info[$i]["pwdlastset"][0], $maxpwdage));
            }

        }
        return $users;
    }

}