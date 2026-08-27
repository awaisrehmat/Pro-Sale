import {ref} from 'vue';

function storedJson(key,fallback){try{return JSON.parse(localStorage.getItem(key)||JSON.stringify(fallback))}catch{return fallback}}

export const authToken=ref(localStorage.getItem('token'));
export const authUser=ref(storedJson('user',null));
export const authPermissions=ref(storedJson('permissions',[]));

export function permissions(){return authPermissions.value}
export function currentUser(){return authUser.value}
export function can(permission){return authPermissions.value.includes(permission)}
export function setAuth({token,user,permissions=[]}){
 authToken.value=token;
 authUser.value=user;
 authPermissions.value=permissions;
 localStorage.setItem('token',token);
 localStorage.setItem('user',JSON.stringify(user));
 localStorage.setItem('permissions',JSON.stringify(permissions));
}
export function clearAuth(){
 authToken.value=null;
 authUser.value=null;
 authPermissions.value=[];
 localStorage.removeItem('token');
 localStorage.removeItem('permissions');
 localStorage.removeItem('user');
}
