import { basename } from 'path';
import { readFile, writeFile, access, constants } from 'fs/promises';

export async function generate(input) {
  const { domain, resource } = await parsePath(input);
  if (resource === null) {
    // Skip if the label file is overlayed by another
    return;
  }
  const targetFile = `types/labels/${domain}.${resource}.d.ts`;
  const content = await readFile(input, 'utf8');
  // @todo switch to a real XML parser,
  // parse possible required parameters and generate
  // types that reflect the required parameters
  // (out of scope for now)
  const labels = [...content.matchAll(/<trans-unit id="([^"]+)"/g)].map(match => match[1]);
  if (labels.length === 0) {
    // Skip if the labels are empty (e.g. deprecated label file)
    return;
  }

  const declaration = createTypeScriptDeclaration(labels);
  await writeFile(targetFile, declaration, { flag: 'w' });
}

async function parsePath(path) {
  const pathParts = path.replace('../typo3/sysext/', '').split('/');
  const domain = pathParts.shift();
  const filename = basename(pathParts.pop(), '.xlf');
  const infix = pathParts.join('/');

  if (infix.startsWith('Configuration/Sets')) {
    pathParts.shift();
    pathParts.shift();
    const setName = pathParts.shift();
    return {
      domain: domain,
      resource: 'sets.' + convertCamelToSnake(setName),
    };
  }

  if (infix.startsWith('Resources/Private/Language')) {
    pathParts.shift();
    pathParts.shift();
    pathParts.shift();
  }
  const namespace = pathParts.map(convertCamelToSnake).map(path => path + '.').join('');
  const name = filename === 'locallang'
    ? 'messages'
    : (
      filename.startsWith('locallang_')
      ? filename.replace(/^locallang_/, '')
      : filename
    );

  if (filename.startsWith('locallang_')) {
    // Test if another file takes priority
    try {
      if (await access(path.replace('/locallang_', '/'), constants.R_OK)) {
        return { domain, resource: null };
      }
    } catch (e) {
      if (!('code' in e && e.code === 'ENOENT')) {
        throw e;
      }
    }
  }

  return {
    domain,
    resource: namespace + convertCamelToSnake(name),
  };
}

function convertCamelToSnake(str) {
  return str.replace(/([a-zA-Z])(?=[A-Z])/g,'$1_').toLowerCase();
}

function createTypeScriptDeclaration(labels) {
  return `import { LabelProvider } from '@typo3/backend/localization/label-provider';
type Labels = {
${labels.map(label => `  '${label}': string,`).join("\n")}
};
declare const provider: LabelProvider<Labels>;
export default provider;
`;
}
