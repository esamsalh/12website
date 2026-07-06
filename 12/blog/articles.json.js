import siteConfig from '../data/site-config.json';

export async function GET() {
  const postImport = import.meta.glob('./blog/*.md', { eager: true });
  const posts = Object.entries(postImport).map(([path, mod]) => {
    const slug = path.replace('./blog/', '').replace('.md', '');
    const fm = mod.frontmatter || {};
    return {
      name: slug + '.md',
      path: 'src/pages/blog/' + slug + '.md',
      slug: slug,
      url: '/blog/' + slug + '.html',
      frontmatter: {
        title: fm.title || slug,
        description: fm.description || '',
        date: fm.date || '',
        author: fm.author || 'مدونة ' + siteConfig.siteName,
        category: fm.category || '',
        tags: fm.tags || []
      }
    };
  });
  posts.sort((a, b) => (b.frontmatter.date || '').localeCompare(a.frontmatter.date || ''));
  return new Response(JSON.stringify({ articles: posts, total: posts.length }), {
    status: 200,
    headers: { 'Content-Type': 'application/json' }
  });
}
