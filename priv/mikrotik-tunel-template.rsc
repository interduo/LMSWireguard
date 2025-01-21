# Login: %%wg_client_mail%%, ID: %%wg_client_configurationid%%;
%%wg_srv_operator_rem%%
%%wg_srv_duplicate%%
%%wg_srv_operator_add%%
/interface wireguard peers add allowed-address="%%wg_client_ip%%" name="%%wg_client_comment%%" interface="%%wg_srv_ifacename%%" public-key="%%wg_client_pubkey%%" endpoint-address="%%wg_client_ip%%";
