import {ref} from 'vue';

function storedJson(key,fallback){try{return JSON.parse(localStorage.getItem(key)||JSON.stringify(fallback))}catch{return fallback}}

export const authToken=ref(localStorage.getItem('token'));
export const authUser=ref(storedJson('user',null));
export const authPermissions=ref(storedJson('permissions',[]));
export const authCompanies=ref(storedJson('companies',[]));
export const currentCompany=ref(storedJson('current_company',null));
export const currentFinancialYear=ref(storedJson('current_financial_year',null));

export function permissions(){return authPermissions.value}
export function currentUser(){return authUser.value}
export function can(permission){return authPermissions.value.includes(permission)}
export function setAuth({token,user,permissions=[],companies,current_company,current_financial_year}){
 authToken.value=token;
 authUser.value=user;
 authPermissions.value=permissions;
 if(companies!==undefined){authCompanies.value=companies;localStorage.setItem('companies',JSON.stringify(companies))}
 if(current_company!==undefined){currentCompany.value=current_company;localStorage.setItem('current_company',JSON.stringify(current_company));localStorage.setItem('company_id',current_company?.id||'')}
 if(current_financial_year!==undefined){currentFinancialYear.value=current_financial_year;localStorage.setItem('current_financial_year',JSON.stringify(current_financial_year))}
 localStorage.setItem('token',token);
 localStorage.setItem('user',JSON.stringify(user));
 localStorage.setItem('permissions',JSON.stringify(permissions));
}
export function clearAuth(){
 authToken.value=null;
 authUser.value=null;
 authPermissions.value=[];
 authCompanies.value=[];currentCompany.value=null;currentFinancialYear.value=null;
 localStorage.removeItem('token');
 localStorage.removeItem('permissions');
 localStorage.removeItem('user');
 localStorage.removeItem('companies');localStorage.removeItem('current_company');localStorage.removeItem('company_id');localStorage.removeItem('current_financial_year');
}
export function selectCompany(company){currentCompany.value=company;localStorage.setItem('current_company',JSON.stringify(company));localStorage.setItem('company_id',company.id)}
