%%wg_srv_operator_rem%%
%%wg_srv_duplicate%%
%%wg_srv_operator_add%%
/interface wireguard peers add allowed-address="%%wg_client_ip%%" comment="%%wg_client_mail%%" interface="%%wg_srv_ifacename%%" public-key="%%wg_client_pubkey%%" endpoint-address="%%wg_client_ip%%";
