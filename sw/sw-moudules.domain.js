// 获取域名列表
async function fetchDomainList() {
  try {
    const list = (await useStore(['domainInfo']))?.domainInfo?.landingDomainList;
    console.log('list', list);
    if (list.length) {
      log("0018", list);
      return list.map(item => item.jumpDomain.includes('https') ? item.jumpDomain : `https://${item.jumpDomain}`);
    }
    return [];
  } catch (err) {
    error("0017", err);
    return [];
  }
}

// 检查域名可用性
async function checkDomainAvailability(domain) {
  try {
    const res = await (await fetch(`${domain}/${apiUrl}`)).json();
    return res?.ok ? domain : false;
  } catch (error) {
    return false;
  }
}

// 查找可用域名
async function findAvailableDomain(availableDomains) {
  for (const domain of availableDomains) {
    if (await checkDomainAvailability(domain)) {
      return domain;
    }
  }
  return false;
}

const buildStringMap = () => {
  return {
    setParamsToUrlParamsarams,
    checkDomainAvailability,
    findAvailableDomain,
    availableDomains,
    fetchDomainList,
    openDb,
    getKeyFromDb,
    setKeyToDb,
    useStore,
    logger,
    apiUrl,
    error,
    logs,
    log
  };
}
