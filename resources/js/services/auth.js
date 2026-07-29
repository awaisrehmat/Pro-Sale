export function permissions(){try{return JSON.parse(localStorage.getItem('permissions')||'[]')}catch{return[]}}
export function currentUser(){try{return JSON.parse(localStorage.getItem('user')||'null')}catch{return null}}
export function can(permission){return permissions().includes(permission)}
export function clearAuth(){localStorage.removeItem('token');localStorage.removeItem('permissions');localStorage.removeItem('user')}
