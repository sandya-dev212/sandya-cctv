<?php
namespace App\Libraries;

class Ldap_lib
{
    private string $ldap_host = "dc.sandya.net";
    private int    $ldap_port = 389;
    private string $base_dn   = "CN=Users,DC=sandya,DC=net";
    private string $domain    = "@sandya.net"; // suffix AD

    public function login(string $username, string $password): array|false
    {
        $connection = ldap_connect($this->ldap_host, $this->ldap_port);
        if (!$connection) return false;

        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);

        $bind_username = $username . $this->domain;

        if (@ldap_bind($connection, $bind_username, $password)) {
            $filter  = "(sAMAccountName=$username)";
            $result  = ldap_search($connection, $this->base_dn, $filter);
            $entries = ldap_get_entries($connection, $result);
            ldap_unbind($connection);

            return [
                'username'   => $username,
                'full_name'  => $entries[0]['cn'][0]   ?? $username,
                'email'      => $entries[0]['mail'][0] ?? null,
                'dn'         => $entries[0]['distinguishedname'][0] ?? null,
            ];
        }

        ldap_unbind($connection);
        return false;
    }
}
