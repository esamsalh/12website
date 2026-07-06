import rss from '@astrojs/rss';
import siteConfig from '../data/site-config.json';

export async function GET(context) {
  const postImport = import.meta.glob('./blog/*.md', { eager: true });
  const posts = Object.values(postImport);
  return rss({
    title: 'مدونة ' + siteConfig.siteName,
    description: siteConfig.siteDescription,
    site: context.site,
    items: posts.map((post) => ({
      title: post.frontmatter.title,
      pubDate: new Date(post.frontmatter.date),
      description: post.frontmatter.description,
      link: post.url,
    })),
    customData: '<language>ar-sa</language>',
  });
}
